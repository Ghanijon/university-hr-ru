-- =====================================================================
-- university-hr — физическая модель базы данных
-- День 7. Физическая модель проекта
-- СУБД: MySQL 8.0+ или MariaDB 10.6+, движок InnoDB
-- Кодировка utf8mb4 — полный UTF-8: кириллица и любые символы Unicode без «????»
-- Запуск:  mysql -u root -p < database/schema.sql
-- ВНИМАНИЕ: скрипт удаляет и создаёт базу заново!
-- =====================================================================

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS university_hr;
CREATE DATABASE university_hr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE university_hr;

-- ---------------------------------------------------------------------
-- Факультеты
-- ---------------------------------------------------------------------
CREATE TABLE faculties (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_faculties_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Кафедры. Внешний ключ на заведующего (employees) добавляется
-- в конце скрипта, когда таблица employees уже существует.
-- ---------------------------------------------------------------------
CREATE TABLE departments (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  faculty_id       INT UNSIGNED NOT NULL,
  name             VARCHAR(150) NOT NULL,
  head_employee_id INT UNSIGNED NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_departments_faculty_name (faculty_id, name),
  CONSTRAINT fk_departments_faculty
    FOREIGN KEY (faculty_id) REFERENCES faculties (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Должности
-- ---------------------------------------------------------------------
CREATE TABLE positions (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title    VARCHAR(100) NOT NULL,
  category ENUM('academic','administrative','support') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_positions_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Сотрудники
-- ---------------------------------------------------------------------
CREATE TABLE employees (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  last_name       VARCHAR(60)  NOT NULL,
  first_name      VARCHAR(60)  NOT NULL,
  middle_name     VARCHAR(60)  NULL,
  birth_date      DATE         NOT NULL,
  gender          ENUM('m','f') NOT NULL,
  phone           VARCHAR(20)  NULL,
  email           VARCHAR(120) NULL,
  department_id   INT UNSIGNED NOT NULL,
  position_id     INT UNSIGNED NOT NULL,
  academic_degree ENUM('none','candidate','doctor','phd') NOT NULL DEFAULT 'none',
  hire_date       DATE         NOT NULL,
  status          ENUM('active','on_leave','dismissed') NOT NULL DEFAULT 'active',
  photo_path      VARCHAR(255) NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employees_email (email),           -- NULL можно хранить много раз
  KEY idx_employees_last_name (last_name),          -- поиск по фамилии
  KEY idx_employees_department (department_id),     -- фильтр и JOIN по кафедре
  CONSTRAINT fk_employees_department
    FOREIGN KEY (department_id) REFERENCES departments (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_employees_position
    FOREIGN KEY (position_id) REFERENCES positions (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Пользователи (учётные записи)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id   INT UNSIGNED NULL,
  username      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,          -- результат password_hash(), не пароль!
  role          ENUM('admin','hr','head','employee') NOT NULL DEFAULT 'employee',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_employee (employee_id),     -- связь 1:1 с сотрудником
  CONSTRAINT fk_users_employee
    FOREIGN KEY (employee_id) REFERENCES employees (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Токены REST API. Сам токен в базе НЕ хранится — только его SHA-256:
-- если базу украдут, по хешу войти в API нельзя.
-- ---------------------------------------------------------------------
CREATE TABLE api_tokens (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64)     NOT NULL,             -- SHA-256 в шестнадцатеричном виде: 64 символа
  expires_at DATETIME     NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_api_tokens_hash (token_hash),   -- поиск токена при каждом запросе — по индексу
  CONSTRAINT fk_api_tokens_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Кадровые приказы
-- ---------------------------------------------------------------------
CREATE TABLE orders (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  number      VARCHAR(30)  NOT NULL,
  type        ENUM('hire','transfer','dismissal','leave') NOT NULL,
  employee_id INT UNSIGNED NOT NULL,
  order_date  DATE         NOT NULL,
  text        TEXT         NOT NULL,
  created_by  INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_number (number),
  KEY idx_orders_employee (employee_id),
  CONSTRAINT fk_orders_employee
    FOREIGN KEY (employee_id) REFERENCES employees (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,     -- сотрудника с приказами удалить нельзя
  CONSTRAINT fk_orders_created_by
    FOREIGN KEY (created_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Заявки на отпуск
-- ---------------------------------------------------------------------
CREATE TABLE leave_requests (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id      INT UNSIGNED NOT NULL,
  type             ENUM('annual','study','unpaid','sick') NOT NULL,
  start_date       DATE         NOT NULL,
  end_date         DATE         NOT NULL,
  reason           VARCHAR(255) NULL,
  status           ENUM('pending','head_approved','approved','rejected') NOT NULL DEFAULT 'pending',
  head_decision_by INT UNSIGNED NULL,
  hr_decision_by   INT UNSIGNED NULL,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_leave_requests_status (status),
  KEY idx_leave_requests_employee (employee_id),
  CONSTRAINT chk_leave_dates CHECK (end_date >= start_date),
  CONSTRAINT fk_leave_employee
    FOREIGN KEY (employee_id) REFERENCES employees (id)
    ON UPDATE CASCADE ON DELETE CASCADE,      -- заявки удаляются вместе с сотрудником
  CONSTRAINT fk_leave_head_decision
    FOREIGN KEY (head_decision_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_leave_hr_decision
    FOREIGN KEY (hr_decision_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Циклическая связь: заведующий кафедрой — это сотрудник
-- ---------------------------------------------------------------------
ALTER TABLE departments
  ADD CONSTRAINT fk_departments_head
    FOREIGN KEY (head_employee_id) REFERENCES employees (id)
    ON UPDATE CASCADE ON DELETE SET NULL;
