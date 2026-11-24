#!/usr/bin/env bash
# Дымовой тест REST API: основной сценарий целиком, через curl.
# Запуск:  tests/api_smoke.sh [адрес]      (по умолчанию http://localhost:8081)
# Создаёт в базе сотрудника, заявку и приказы; вернуть исходные данные: ./demo.sh reset ru
set -uo pipefail

API="${1:-http://localhost:8081}/api/v1"
FAILED=0
BODY=""

# call МЕТОД ПУТЬ ТОКЕН [JSON] — ответ в $BODY, код в $CODE
call() {
  local args=(-s -X "$1" -H 'Content-Type: application/json' -w $'\n%{http_code}')
  [ -n "$3" ] && args+=(-H "Authorization: Bearer $3")
  [ -n "${4:-}" ] && args+=(-d "$4")
  local out; out="$(curl "${args[@]}" "$API$2")"
  CODE="${out##*$'\n'}"; BODY="${out%$'\n'*}"
}

# expect КОД "описание" ["текст, который должен быть в ответе"]
expect() {
  if [ "$CODE" = "$1" ] && [[ "$BODY" == *"${3:-}"* ]]; then
    printf '  ok   %s  %s\n' "$CODE" "$2"
  else
    printf '  FAIL %s (ждали %s %s)  %s\n       %s\n' "$CODE" "$1" "${3:-}" "$2" "$BODY"; FAILED=1
  fi
}

field() { printf '%s' "$BODY" | php -r '$v = json_decode(stream_get_contents(STDIN), true); foreach (explode(".", $argv[1]) as $k) { $v = $v[$k] ?? null; } echo $v;' "$1"; }
login() { call POST /auth/login "" "{\"username\":\"$1\",\"password\":\"Secret123!\"}"; expect 200 "вход: $1" '"token"'; TOKEN="$(field token)"; }

echo "Проверяю $API"
login hr;       HR="$TOKEN"
login head_pis; HEAD="$TOKEN"
login teacher1; TEACHER="$TOKEN"
call POST /auth/login "" '{"username":"hr","password":"wrong"}'; expect 401 "неверный пароль" invalid_credentials

call GET "/employees?per_page=2&sort=-hire_date&status=active,on_leave" "$HR"; expect 200 "список" '"per_page":2'
call GET "/employees?sort=password_hash" "$HR"; expect 422 "сортировка не из белого списка" '"sort"'
call GET "/employees?q=%27%20OR%20%271%27=%271" "$HR"; expect 200 "инъекция в поиске ничего не нашла" '"total":0'
call GET "/employees?q=%25" "$HR"; expect 200 "символ % в поиске — обычный символ" '"total":0'

EMAIL="smoke$(date +%s)$$@example.tj"
NEW='{"last_name":"Тестов","first_name":"Тест","birth_date":"1990-01-01","gender":"m","email":"'$EMAIL'","department_id":2,"position_id":5,"academic_degree":"none","hire_date":"2020-09-01","status":"active"}'
call POST /employees "$HR" "$NEW"; expect 201 "создание сотрудника" "$EMAIL"; ID="$(field data.id)"
call GET "/employees/$ID" "$HR"; expect 200 "карточка" '"department_id":2'
call PUT "/employees/$ID" "$HR" "${NEW/Тестов/Тестова}"; expect 200 "изменение" 'Тестова'
call POST /employees "$HR" "$NEW"; expect 422 "e-mail уже занят" '"email"'
call POST /employees "$HR" '{"last_name":""}'; expect 422 "ошибка валидации" validation_failed
call POST /employees "$HR" '{"last_name":'; expect 400 "битый JSON" bad_json

call GET /employees ""; expect 401 "без токена" unauthorized
call GET /employees "$TEACHER"; expect 403 "чужая роль: список" forbidden
call POST /employees "$TEACHER" "$NEW"; expect 403 "чужая роль: создание" forbidden
call GET "/employees/$ID" "$HEAD"; expect 403 "чужая кафедра" forbidden
call GET /employees/999999 "$HR"; expect 404 "нет такого сотрудника" not_found
call PATCH /employees "$HR"; expect 405 "метод не поддерживается" method_not_allowed

TODAY="$(php -r 'date_default_timezone_set("Asia/Dushanbe"); echo date("Y-m-d");')"
START="$(php -r 'echo date("Y-m-d", strtotime("+30 days"));')"
END="$(php -r 'echo date("Y-m-d", strtotime("+39 days"));')"
MOVE="{\"department_id\":1,\"position_id\":4,\"date\":\"$TODAY\"}"
call POST "/employees/$ID/transfer" "$HEAD" "$MOVE"; expect 403 "перевод: нет прав" forbidden
call POST "/employees/$ID/transfer" "$HR" "$MOVE"; expect 200 "перевод и приказ" 'ПЕ-'
call POST "/employees/$ID/transfer" "$HR" "$MOVE"; expect 422 "перевод на то же место" '"position_id"'
call POST /employees/1/transfer "$HR" "{\"department_id\":2,\"position_id\":4,\"date\":\"$TODAY\"}"; expect 409 "перевод заведующего" conflict
call DELETE "/employees/$ID" "$HR"; expect 409 "удаление сотрудника с приказом" conflict

LEAVE="{\"employee_id\":$ID,\"type\":\"annual\",\"start_date\":\"$START\",\"end_date\":\"$END\"}"
call POST /leaves "$HR" "$LEAVE"; expect 201 "заявка на отпуск" pending; LID="$(field data.id)"
call POST /leaves "$HR" "$LEAVE"; expect 422 "пересечение дат" '"start_date"'
call POST "/leaves/$LID/approve" "$TEACHER"; expect 403 "утверждение: нет прав" forbidden
call POST "/leaves/$LID/approve" "$HR"; expect 403 "ОК раньше заведующего" forbidden
call POST "/leaves/$LID/approve" "$HEAD"; expect 200 "согласование заведующим" head_approved
call POST "/leaves/$LID/approve" "$HEAD"; expect 409 "повторное согласование" conflict
call POST "/leaves/$LID/approve" "$HR" '{"expected_status":"pending"}'; expect 409 "устаревший статус на экране" conflict
call POST "/leaves/$LID/approve" "$HR"; expect 200 "утверждение ОК" '"approved"'
call POST "/leaves/$LID/approve" "$HR"; expect 409 "повторное утверждение" conflict
call POST "/leaves/$LID/reject" "$HR"; expect 409 "отклонить утверждённую" conflict
call GET "/leaves?status=approved" "$TEACHER"; expect 200 "сотрудник видит только свои заявки" '"total":0'

for p in /departments /positions /reports/staff; do call GET "$p" "$HR"; expect 200 "$p" '"data"'; done
call GET /departments "$TEACHER"; expect 403 "справочник: нет прав" forbidden

TEMP="${NEW/$EMAIL/del-$EMAIL}"
call POST /employees "$HR" "$TEMP"; ID2="$(field data.id)"
call DELETE "/employees/$ID2" "$HR"; expect 204 "удаление сотрудника без приказов"
call POST /auth/logout "$HR"; expect 204 "выход"
call GET /departments "$HR"; expect 401 "токен после выхода не действует" unauthorized

[ "$FAILED" = 0 ] && echo "Все проверки пройдены" || echo "Есть ошибки"
exit "$FAILED"
