# 16. Публикация проекта на веб-сервере

Пошаговая инструкция: как опубликовать **university-hr** на VDS с Ubuntu 24.04 LTS:
Nginx + PHP-FPM + MySQL, HTTPS от Let's Encrypt, обновление через `git pull`, ежедневные резервные копии.

Условные обозначения (замените на свои):

| Что | Пример в инструкции |
|---|---|
| Домен | `hr.example.tj` |
| IP-адрес VDS | `203.0.113.10` |
| Пользователь на сервере | `deploy` |
| Папка проекта | `/var/www/university-hr` |
| Пароль пользователя БД | `S1lnyi_Parol_2026` (придумайте свой!) |

## 16.1. Как это устроено

```
Браузер ──HTTPS:443──► Nginx ──FastCGI (unix-сокет)──► PHP-FPM ──► public/index.php ──PDO──► MySQL (127.0.0.1:3306)
                         │
                         └── отдаёт сам: CSS, JS, фото из public/
```

- **Nginx** принимает запросы, отдаёт статические файлы и передаёт PHP-запросы в PHP-FPM.
- **PHP-FPM** (FastCGI Process Manager) держит пул процессов PHP и выполняет `index.php`.
- **MySQL** слушает только `127.0.0.1` — снаружи к базе подключиться нельзя.
- Диаграмма развёртывания — `docs/07-deployment.puml`.

## 16.2. Домен и DNS

1. Купите домен (зона `.tj` — у аккредитованных регистраторов) или используйте поддомен института.
2. В панели DNS создайте запись типа **A**: `hr.example.tj → 203.0.113.10`.
3. Подождите обновления DNS (от нескольких минут до суток) и проверьте:

```bash
nslookup hr.example.tj          # Windows / Linux
dig +short hr.example.tj        # Linux, должен вывести 203.0.113.10
```

## 16.3. Первый вход и базовая защита сервера

```bash
ssh root@203.0.113.10

# Обновляем систему
apt update && apt upgrade -y

# Отдельный пользователь для работы (не работаем под root)
adduser deploy
usermod -aG sudo deploy

# Брандмауэр: открываем только SSH, HTTP и HTTPS
ufw allow OpenSSH
ufw allow 'Nginx Full'      # правило появится после установки nginx (шаг 16.4) — тогда выполните ещё раз
ufw enable
ufw status
```

Дальше работаем под `deploy`: `ssh deploy@203.0.113.10`.

## 16.4. Установка Nginx, PHP-FPM, MySQL, Git, Composer

```bash
sudo apt install -y nginx mysql-server git unzip \
    php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-intl php8.3-curl
# (вместо mysql-server можно mariadb-server — проект работает с обеими СУБД)

sudo ufw allow 'Nginx Full'

# Composer
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Проверка
nginx -v && php -v && mysql --version && composer --version
systemctl status php8.3-fpm --no-pager
```

Откройте `http://203.0.113.10` — должна появиться страница «Welcome to nginx!».

## 16.5. Настройка PHP

Файл `/etc/php/8.3/fpm/php.ini` (найдите строки и измените):

```ini
display_errors = Off          ; ошибки — только в журнал, не пользователю
log_errors = On
upload_max_filesize = 2M      ; фото сотрудника — до 2 МБ
post_max_size = 8M
date.timezone = Asia/Dushanbe
expose_php = Off              ; не показывать версию PHP в заголовках
```

```bash
sudo systemctl restart php8.3-fpm
```

## 16.6. База данных: создание и перенос

### Безопасная установка MySQL
```bash
sudo mysql_secure_installation     # удалить анонимных пользователей и тестовую БД
```

### Отдельный пользователь для приложения
Приложение **не должно** подключаться под `root`. Создаём пользователя только с правами на свою базу:

```bash
sudo mysql
```
```sql
CREATE DATABASE university_hr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hr_app'@'localhost' IDENTIFIED BY 'S1lnyi_Parol_2026';
GRANT SELECT, INSERT, UPDATE, DELETE ON university_hr.* TO 'hr_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Вариант А: чистая установка с тестовыми данными
`schema.sql` сам удаляет и создаёт базу, поэтому выполняем его под администратором MySQL:

```bash
sudo mysql < /var/www/university-hr/database/schema.sql
sudo mysql university_hr < /var/www/university-hr/database/seed.sql
```
(Сначала выполните шаг 16.7, чтобы файлы оказались на сервере.)

> Внимание: после `DROP DATABASE` права пользователя `hr_app` на базу сохраняются (они выданы на имя
> `university_hr.*`), повторять `GRANT` не нужно.

### Вариант Б: перенос базы с локального компьютера (XAMPP)
На своём компьютере (Windows, папка `C:\xampp\mysql\bin`):

```bash
mysqldump -u root --default-character-set=utf8mb4 --single-transaction --routines university_hr > university_hr.sql
scp university_hr.sql deploy@203.0.113.10:/tmp/
```

На сервере:
```bash
sudo mysql university_hr < /tmp/university_hr.sql
rm /tmp/university_hr.sql
# Проверка кодировки: кириллица должна отображаться нормально
sudo mysql -e "SET NAMES utf8mb4; SELECT last_name FROM university_hr.employees LIMIT 5;"
```

Через phpMyAdmin: «Экспорт» → формат SQL → кодировка utf8mb4; на сервере импорт той же командой `mysql`.

## 16.7. Код приложения: клонирование из Git

```bash
sudo mkdir -p /var/www/university-hr
sudo chown deploy:deploy /var/www/university-hr
git clone https://github.com/<ваш-аккаунт>/university-hr.git /var/www/university-hr
cd /var/www/university-hr

# Зависимости: только автозагрузка, без PHPUnit (--no-dev), с оптимизацией
composer install --no-dev --optimize-autoloader
```

### Права на папки
Nginx и PHP-FPM работают от пользователя `www-data`. Код ему нужно только **читать**,
писать — только в папку загрузок:

```bash
sudo chown -R deploy:www-data /var/www/university-hr
sudo find /var/www/university-hr -type d -exec chmod 750 {} \;
sudo find /var/www/university-hr -type f -exec chmod 640 {} \;
sudo chmod 770 /var/www/university-hr/public/uploads
```

## 16.8. Настройки: config.php или переменные окружения

Файл `config/config.php` не хранится в Git (в нём пароль). Создаём его на сервере из примера:

```bash
cd /var/www/university-hr
cp config/config.example.php config/config.php
nano config/config.php
```

Измените значения:
```php
'host' => getenv('DB_HOST') ?: '127.0.0.1',
'user' => getenv('DB_USER') ?: 'hr_app',
'pass' => getenv('DB_PASS') ?: 'S1lnyi_Parol_2026',
...
'debug' => (getenv('APP_DEBUG') ?: '0') === '1',     // на сервере — 0!
```

```bash
chmod 640 config/config.php      # читать могут только deploy и группа www-data
```

**Вариант с переменными окружения** (аналог файла `.env`): значения можно задать в пуле PHP-FPM
`/etc/php/8.3/fpm/pool.d/www.conf`, тогда `config.php` можно не править:

```ini
env[DB_USER] = hr_app
env[DB_PASS] = S1lnyi_Parol_2026
env[APP_DEBUG] = 0
env[APP_TIMEZONE] = Asia/Dushanbe
```
```bash
sudo systemctl restart php8.3-fpm
```

## 16.9. Nginx

Конфигурация лежит в репозитории: `deploy/nginx.conf`. Главное в ней:
- `root` указывает на папку **public** — исходный код, конфиг и шаблоны снаружи недоступны;
- `try_files $uri $uri/ /index.php?$query_string` — front controller;
- выполняется **только** `index.php`, любой другой `.php` (например, подброшенный в `uploads/`) даёт 404;
- скрытые файлы (`.htaccess`, `.env`) закрыты;
- HTTP перенаправляется на HTTPS, добавлены заголовки безопасности.

```bash
sudo cp /var/www/university-hr/deploy/nginx.conf /etc/nginx/sites-available/university-hr
sudo sed -i 's/hr.example.tj/ВАШ_ДОМЕН/g' /etc/nginx/sites-available/university-hr
sudo ln -s /etc/nginx/sites-available/university-hr /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
```

Пока сертификата нет, Nginx с этим файлом не запустится (он ссылается на файлы сертификата).
Поэтому сначала получаем сертификат (шаг 16.10), потом проверяем конфигурацию.

## 16.10. HTTPS: сертификат Let's Encrypt (certbot)

```bash
sudo apt install -y certbot

# Получаем сертификат. На время проверки certbot сам слушает 80-й порт,
# поэтому Nginx останавливается и запускается снова (хуки сохранятся и для продления).
sudo certbot certonly --standalone -d hr.example.tj \
     --pre-hook "systemctl stop nginx" --post-hook "systemctl start nginx" \
     -m admin@example.tj --agree-tos --no-eff-email

sudo nginx -t                 # должно быть: syntax is ok, test is successful
sudo systemctl reload nginx
```

Сертификат действует 90 дней. Certbot уже установил таймер автоматического продления, проверка:

```bash
systemctl list-timers | grep certbot
sudo certbot renew --dry-run
```

Альтернатива: пакет `python3-certbot-nginx` и команда `sudo certbot --nginx -d hr.example.tj` —
certbot сам допишет SSL-настройки в конфигурацию Nginx.

Проверка: откройте `https://hr.example.tj/login`, войдите как `admin` / `Secret123!`.
**Сразу смените пароли тестовых пользователей** (или не загружайте `seed.sql` на рабочий сервер).
Новый хеш пароля можно получить так:

```bash
php -r 'echo password_hash("НовыйПароль!2026", PASSWORD_DEFAULT), "\n";'
sudo mysql -e "UPDATE university_hr.users SET password_hash='<хеш>' WHERE username='admin';"
```

## 16.11. Деплой новых версий через Git

Обновление после `git push` с локального компьютера:

```bash
cd /var/www/university-hr
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
sudo systemctl reload php8.3-fpm      # сбрасывает OPcache, чтобы PHP увидел новые файлы
```

Удобно оформить это скриптом `~/deploy.sh`:

```bash
#!/bin/bash
set -e                                   # остановиться при первой ошибке
cd /var/www/university-hr
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader --no-interaction
sudo systemctl reload php8.3-fpm
echo "Деплой завершён: $(git log --oneline -1)"
```
```bash
chmod +x ~/deploy.sh && ~/deploy.sh
```

Если изменилась структура БД — выполните SQL-скрипт миграции **до** обновления кода и **после** резервной копии.
Откат: `git log --oneline` → `git checkout <хеш>` (или `git revert`), затем тот же `composer install`.

## 16.12. Резервное копирование (cron + mysqldump)

### Пользователь только для бэкапа
```sql
CREATE USER 'hr_backup'@'localhost' IDENTIFIED BY 'Backup_Parol_2026';
GRANT SELECT, LOCK TABLES, SHOW VIEW, TRIGGER, EVENT ON university_hr.* TO 'hr_backup'@'localhost';
```

Пароль храним в файле, а не в командной строке (иначе его видно в списке процессов):

```bash
cat > ~/.my.cnf <<'EOF'
[mysqldump]
user=hr_backup
password=Backup_Parol_2026
EOF
chmod 600 ~/.my.cnf
```

### Скрипт `~/backup.sh`
```bash
#!/bin/bash
set -e
DIR=/var/backups/university-hr
DATE=$(date +%F_%H-%M)
mkdir -p "$DIR"

# База данных: согласованный снимок без блокировки таблиц (InnoDB)
mysqldump --single-transaction --default-character-set=utf8mb4 university_hr | gzip > "$DIR/db_$DATE.sql.gz"

# Фото сотрудников
tar -czf "$DIR/uploads_$DATE.tar.gz" -C /var/www/university-hr/public uploads

# Храним 14 дней
find "$DIR" -type f -mtime +14 -delete
```

```bash
sudo mkdir -p /var/backups/university-hr && sudo chown deploy:deploy /var/backups/university-hr
chmod +x ~/backup.sh
~/backup.sh && ls -lh /var/backups/university-hr      # пробный запуск
```

### Расписание cron
```bash
crontab -e
```
```cron
# мин час день месяц день_недели  команда
30 2 * * * /home/deploy/backup.sh >> /home/deploy/backup.log 2>&1
```
Каждый день в 02:30. Копии лучше дополнительно переносить на другой сервер или в облако (`rsync`, `rclone`):
резервная копия на том же диске не спасёт при поломке диска.

### Восстановление
```bash
gunzip < /var/backups/university-hr/db_2026-11-20_02-30.sql.gz | sudo mysql university_hr
sudo tar -xzf /var/backups/university-hr/uploads_2026-11-20_02-30.tar.gz -C /var/www/university-hr/public
```
Проверяйте восстановление хотя бы раз в месяц — непроверенная копия может оказаться пустой.

## 16.13. Проверка после публикации (чек-лист)

- [ ] `https://` открывается без предупреждений, `http://` перенаправляет на `https://`.
- [ ] `https://hr.example.tj/config/config.php` и `/src/Core/Auth.php` → 404 (код не виден).
- [ ] Вход под каждой ролью работает, русские буквы отображаются правильно.
- [ ] Загрузка фото работает (права на `public/uploads`).
- [ ] В ответе сервера cookie `UHRSESSID` с флагами `Secure; HttpOnly; SameSite=Lax` (DevTools → Network).
- [ ] При ошибке пользователь не видит текст исключения (`debug = false`).
- [ ] `~/backup.sh` создаёт архивы, cron-задача добавлена.
- [ ] `sudo certbot renew --dry-run` проходит.

## 16.14. Частые ошибки

| Симптом | Причина | Решение |
|---|---|---|
| 502 Bad Gateway | PHP-FPM не запущен или другой путь к сокету | `systemctl status php8.3-fpm`, сверить `fastcgi_pass` с `ls /run/php/` |
| 404 на всех страницах, кроме главной | Нет `try_files … /index.php` | Проверить блок `location /` |
| «????» вместо букв | Импорт без `--default-character-set=utf8mb4` | Повторить импорт с указанием кодировки |
| `SQLSTATE[HY000] [1045] Access denied` | Неверный пользователь или пароль в config.php | Проверить `mysql -u hr_app -p university_hr` |
| Фото не загружается | Нет прав на запись в `public/uploads` | `chmod 770`, группа `www-data` |
| «Слишком большой запрос» (413) | Файл больше `client_max_body_size` / `post_max_size` | Уменьшить файл или увеличить лимиты |
| Старый код после `git pull` | Включён OPcache | `sudo systemctl reload php8.3-fpm` |

## 16.15. Рубежный контроль РК-2 (вопросы)

1. Зачем Nginx нужен PHP-FPM? Почему Nginx не выполняет PHP сам?
2. Почему DocumentRoot — папка `public`, а не корень проекта?
3. Чем запись DNS типа A отличается от CNAME?
4. Почему приложение подключается к MySQL не под `root`? Какие права нужны `hr_app`?
5. Как Let's Encrypt проверяет, что домен принадлежит вам?
6. Что произойдёт, если `config.php` с паролем попадёт в публичный репозиторий? Что делать?
7. Почему `mysqldump --single-transaction` не блокирует работу пользователей?
