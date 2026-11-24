# university-hr — кадровая информационная система вуза

Учебный сквозной проект курса «Технологии и языки интернет-программирования» (4 курс, специальность 1-400101).

Проект показывает полный путь веб-приложения: от анализа предметной области до публикации на VDS.
Каждый учебный день добавляет к проекту свою часть, а состояние на конец дня отмечено git-тегом
`day-01` … `day-16`.

Предметная область — отдел кадров вуза (на примере Политехнического института ТТУ в Худжанде).
**Все персональные данные в проекте вымышлены.**

## Возможности

- Вход по логину и паролю, четыре роли: администратор, отдел кадров, заведующий кафедрой, сотрудник.
- Сотрудники: список с поиском, фильтрами и пагинацией, карточка, добавление, редактирование, удаление, фото.
- Справочник кафедр и должностей.
- Заявки на отпуск с двухуровневым согласованием: **pending → head_approved → approved / rejected**;
  при утверждении автоматически создаётся приказ.
- Отчёт «Штат по кафедрам» (с версией для печати).
- REST API v1 с Bearer-токенами, описание — Swagger UI `/api/docs.html`.
- Живой поиск сотрудников через JSON API `/api/employees?q=`.
- Безопасность: `password_hash`, prepared statements (PDO), экранирование вывода, CSRF-токены,
  проверка ролей, безопасная загрузка файлов.

## Стек

PHP 8.2+ (без фреймворка, собственный мини-MVC) · MySQL 8 / MariaDB 10.6+ (PDO) · HTML5, CSS3,
Bootstrap 5.3 (CDN) · ванильный JavaScript · Composer (PSR-4, PHPUnit) · PlantUML · Nginx + PHP-FPM.

## Быстрый старт

### Вариант 1. XAMPP (Windows)

1. Скопируйте папку проекта в `C:\xampp\htdocs\university-hr`.
2. Запустите в XAMPP Control Panel модули **Apache** и **MySQL**.
3. Создайте базу и загрузите тестовые данные (кнопка Shell в XAMPP или phpMyAdmin → «Импорт»):
   ```bash
   cd C:\xampp\htdocs\university-hr
   C:\xampp\mysql\bin\mysql -u root < database\schema.sql
   C:\xampp\mysql\bin\mysql -u root university_hr < database\seed.sql
   ```
4. (Необязательно) `composer install` — установит автозагрузчик и PHPUnit. Без него проект тоже работает:
   в `public/index.php` есть запасной автозагрузчик.
5. Откройте **http://localhost/university-hr/** (или `http://localhost/university-hr/public/`).

Настройки по умолчанию (`config/config.example.php`) подходят для XAMPP: `root` без пароля, база `university_hr`.
Если у вас другие параметры — скопируйте файл в `config/config.php` и измените его.

### Вариант 2. Встроенный сервер PHP (macOS, Linux, Windows)

**1. Установите программы:** PHP 8.2+ (расширения `pdo_mysql`, `mbstring`, `fileinfo`), Composer, MySQL 8 или MariaDB 10.6+.

```bash
brew install php composer mysql && brew services start mysql                 # macOS
sudo apt install php-cli php-mysql php-mbstring php-xml composer mysql-server   # Ubuntu
php -v && php -m | grep pdo_mysql                                            # проверка
```

Windows: XAMPP (PHP и MySQL) и Composer с getcomposer.org.

**2. Скачайте проект.** Репозиторий приватный: нужен доступ и вход в GitHub (`gh auth login`).

```bash
git clone https://github.com/Ghanijon/university-hr-ru.git university-hr
cd university-hr
git switch live/2026-11-api      # версия с REST API и Swagger
composer install
```

**3. Создайте базу и пользователя.** Войдите в MySQL как администратор: `mysql -u root`
(если у root есть пароль — `mysql -u root -p`, в Ubuntu — `sudo mysql`) и выполните:

```sql
CREATE DATABASE university_hr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hr_demo'@'localhost' IDENTIFIED BY 'hr_demo_pass';
CREATE USER 'hr_demo'@'127.0.0.1' IDENTIFIED BY 'hr_demo_pass';
GRANT ALL PRIVILEGES ON university_hr.* TO 'hr_demo'@'localhost';
GRANT ALL PRIVILEGES ON university_hr.* TO 'hr_demo'@'127.0.0.1';
```

**4. Загрузите дамп** (структура и тестовые данные; пароль `hr_demo_pass`):

```bash
mysql -h 127.0.0.1 -u hr_demo -p university_hr < database/dump.sql
```

В Windows PowerShell нет `<`: выполняйте команду в `cmd` или импортируйте файл через phpMyAdmin.

**5. Настройте подключение:**

```bash
cp config/config.example.php config/config.php      # Windows: copy config\config.example.php config\config.php
```

В `config/config.php` укажите в разделе `db`: пользователь `hr_demo`, пароль `hr_demo_pass`
(см. «Строки подключения»). Этот файл в Git не попадает.

**6. Запустите:**

```bash
php -S localhost:8081 -t public
```

- приложение: **http://localhost:8081**
- Swagger: **http://localhost:8081/api/docs.html** (нужен интернет)

### Строки подключения к базе

| Параметр | Значение для учебного стенда |
|---|---|
| Сервер и порт | `127.0.0.1:3306` |
| База | `university_hr` |
| Пользователь / пароль | `hr_demo` / `hr_demo_pass` |
| Кодировка | `utf8mb4` |

> Это пароль **только для локальной учебной базы**. На сервере задайте другой, в Git его не сохраняйте.

PDO (PHP):

```php
new PDO('mysql:host=127.0.0.1;port=3306;dbname=university_hr;charset=utf8mb4', 'hr_demo', 'hr_demo_pass');
```

`config/config.php`:

```php
'db' => [
    'host' => '127.0.0.1', 'port' => 3306, 'name' => 'university_hr',
    'user' => 'hr_demo',   'pass' => 'hr_demo_pass',
],
```

Переменные окружения вместо `config.php` (их читает `config.example.php`):

```bash
DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=university_hr DB_USER=hr_demo DB_PASS=hr_demo_pass php -S localhost:8081 -t public
```

| Клиент | Строка |
|---|---|
| Терминал | `mysql -h 127.0.0.1 -P 3306 -u hr_demo -p university_hr` |
| URI (TablePlus и др.) | `mysql://hr_demo:hr_demo_pass@127.0.0.1:3306/university_hr` |
| JDBC (DBeaver, DataGrip) | `jdbc:mysql://127.0.0.1:3306/university_hr?characterEncoding=UTF-8` |

### Дамп базы

```bash
# восстановить исходные данные (таблицы будут заменены)
mysql -h 127.0.0.1 -u hr_demo -p university_hr < database/dump.sql

# сделать новый дамп; токены API не выгружаем
mysqldump -h 127.0.0.1 -u hr_demo -p --single-transaction --no-tablespaces --ignore-table=university_hr.api_tokens university_hr > backup.sql
```

Другой способ: `database/schema.sql` + `database/seed.sql`. Внимание: `schema.sql` удаляет базу и создаёт её заново,
для этого нужны права администратора.

### Если не работает

| Сообщение | Что делать |
|---|---|
| `could not find driver` | Включите расширение `pdo_mysql` в PHP |
| `Access denied for user` | Повторите шаг 3, проверьте `config/config.php` |
| `Connection refused` | MySQL не запущен: `brew services start mysql` / `sudo systemctl start mysql` |
| `Unknown database` | База не создана (шаг 3) |
| `Table ... doesn't exist` | Не загружен дамп (шаг 4) |
| `Address already in use` | Порт занят: запустите на другом, например `localhost:8090` |

### Тестовые логины

Пароль у всех: **`Secret123!`**

| Логин | Роль | Что посмотреть |
|---|---|---|
| `admin` | Администратор | Всё: сотрудники, заявки обоих уровней |
| `hr` | Отдел кадров | Добавление сотрудника с фото, утверждение заявок, отчёт |
| `head_pis` | Зав. кафедрой программирования и ИС | Только своя кафедра; согласование заявки `teacher1` |
| `teacher1` | Сотрудник (Рахимов Фарход) | Свой профиль, подача заявки на отпуск |

Сценарий для демонстрации: `teacher1` подаёт заявку → `head_pis` согласует → `hr` утверждает
(в таблице `orders` появляется приказ).

## Маршруты

| Метод | Маршрут | Назначение | Роли |
|---|---|---|---|
| GET, POST | `/login` | Вход | гость |
| POST | `/logout` | Выход | все |
| GET | `/` | Главная (dashboard) | все |
| GET | `/employees` | Список (`?q=&department_id=&status=&page=`) | admin, hr, head |
| GET, POST | `/employees/create` | Новый сотрудник | admin, hr |
| GET | `/employees/{id}` | Карточка | admin, hr, head (своя кафедра), employee (свой профиль) |
| GET, POST | `/employees/{id}/edit` | Редактирование | admin, hr |
| POST | `/employees/{id}/delete` | Удаление | admin, hr |
| GET | `/departments` | Кафедры и должности | admin, hr, head |
| GET | `/leaves` | Заявки на отпуск (`?status=`) | все |
| GET, POST | `/leaves/create` | Новая заявка | все |
| POST | `/leaves/{id}/approve` | Согласовать / утвердить | admin, hr, head |
| POST | `/leaves/{id}/reject` | Отклонить | admin, hr, head |
| GET | `/reports/staff` | Отчёт «Штат по кафедрам» | admin, hr, head |
| GET | `/api/employees?q=` | JSON для живого поиска | admin, hr, head |

## Структура проекта

```
university-hr/
  README.md
  composer.json, phpunit.xml, .htaccess (XAMPP: перенаправление в public/)
  config/config.example.php        настройки (config.php — в .gitignore)
  database/                        schema.sql, seed.sql, queries.sql, dump.sql (полный дамп)
  deploy/nginx.conf                конфигурация Nginx + PHP-FPM
  docs/                            документация дней 1–9, 15, 16 (Markdown + PlantUML)
  public/                          DocumentRoot
    index.php                      front controller и таблица маршрутов
    assets/css/app.css, assets/js/app.js
    static/                        статическая вёрстка дней 10–11
    uploads/                       фото сотрудников
  src/
    Core/          Router, Database, View, Session, Csrf, Auth, Validator
    Controllers/   Controller (базовый), Auth-, Dashboard-, Employee-, Department-, Leave-, ReportController
    Repositories/  Employee-, Department-, Position-, LeaveRequest-, UserRepository
    Services/      LeaveRequestRules (правила дат и статусов), PhotoUploader
  views/           layout.php, partials/, auth/, dashboard/, employees/, departments/, leaves/, reports/, errors/
  tests/           ValidatorTest, LeaveRequestRulesTest (PHPUnit)
```

## Проект по дням

```bash
git tag                          # список учебных дней
git checkout day-07              # состояние проекта на конец 7-го дня
git checkout main                # вернуться к последней версии
git diff day-12 day-13 --stat    # что изменилось за день 13
```

| Тег | Тема дня | Что добавлено |
|---|---|---|
| day-01 | Анализ предметной области | README, `docs/01-domain-analysis.md` — роли, процессы, сущности, глоссарий |
| day-02 | Сравнительная матрица | `docs/02-comparison-matrix.md` — 1С:ЗУП, BambooHR, Odoo HR, OrangeHRM, Frappe HR × 10 критериев |
| day-03 | Краткое техническое задание | `docs/03-short-spec.md` — требования, user stories, MoSCoW, MVP |
| day-04 | Модель поведения проекта | `docs/04-behavior/` — use case, activity «Оформление отпуска», sequence «Согласование» |
| day-05 | Модель структуры проекта | `docs/05-structure/` — диаграмма классов (MVC), диаграмма компонентов |
| day-06 | Логическая структура базы данных | `docs/06-er-diagram.puml`, `docs/06-logical-model.md` — ER, нормализация до 3НФ |
| day-07 | Физическая модель проекта | `database/schema.sql`, `seed.sql`, `queries.sql`, `docs/07-deployment.puml` |
| day-08 | Техническое задание проекта | `docs/08-technical-specification.md` — полное ТЗ по мотивам ГОСТ 34.602 |
| day-09 | Разработка дизайна | `docs/09-design/` — UI-kit и HTML-вайрфреймы (замена Figma-файла) |
| day-10 | FRONTEND (1) | `public/static/*.html` — статическая вёрстка, `assets/css/app.css` |
| day-11 | FRONTEND (2) | `assets/js/app.js` — валидация формы отпуска, живой поиск через временную заглушку `public/api/employees/index.php`, модальное окно удаления |
| day-12 | BACKEND (1) | Composer, front controller, `Router`, `View`, шаблоны; данные — заглушки |
| day-13 | Модули работы с базой данных | `Database` (PDO), репозитории, CRUD, поиск/фильтр/пагинация, JOIN, отчёт |
| day-14 | BACKEND (2) | `Session`, `Auth`, роли, `Csrf`, загрузка фото, заявки на отпуск и согласование, `/api/employees` |
| day-15 | Тестирование и исправление модулей | PHPUnit-тесты, `docs/15-test-cases.md`, три исправления `fix:` по найденным багам |
| day-16 | Публикация проекта на веб-сервере | `deploy/nginx.conf`, `docs/16-deploy.md`, исправление для XAMPP, этот README |

## Диаграммы

Исходники UML лежат в `docs/**/*.puml`. Просмотр: расширение PlantUML для VS Code, сайт
plantuml.com или команда `plantuml -tpng docs/04-behavior/*.puml`.

## Тесты

```bash
composer install
DB_NAME=university_hr vendor/bin/phpunit
tests/api_smoke.sh http://localhost:8081     # сервер должен быть запущен; добавляет записи в базу
```

75 тестов: поля формы, правила заявки на отпуск, безопасная сборка SQL и откат транзакции перевода
(последний без базы пропускается).
Ручные тест-кейсы и баг-репорты — `docs/15-test-cases.md`.

## Публикация

Пошаговая инструкция для Ubuntu VDS (Nginx, PHP-FPM, MySQL, перенос БД, HTTPS через certbot,
деплой через `git pull`, бэкапы через cron) — `docs/16-deploy.md`.

## Ограничения учебной версии

- Нет интерфейса для управления пользователями и справочниками: учётные записи и кафедры
  добавляются SQL-запросами (`database/seed.sql` как образец).
- Приказы создаются автоматически только при утверждении отпуска; приказы о приёме,
  переводе и увольнении есть в базе, но не имеют своих страниц.
- Статус сотрудника «В отпуске» меняет отдел кадров вручную (см. `docs/06-logical-model.md`, «осознанная денормализация»).

## Литература

Томсон Л., Веллинг Л. «Разработка веб-приложений с помощью PHP и MySQL»; Кузнецов М., Симдянов И. «PHP 5»;
Ашурова Ш. Н. «Программирование на языке PHP» (2012); Худойбердиев Х. А. «Язык запросов SQL» (2010);
php.net, dev.mysql.com, getbootstrap.com, developer.mozilla.org, OWASP Cheat Sheet Series.
