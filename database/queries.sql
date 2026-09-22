-- =====================================================================
-- university-hr — контрольные запросы (день 7)
-- Выполняйте по одному в phpMyAdmin (вкладка SQL) или в консоли mysql.
-- =====================================================================
USE university_hr;
SET NAMES utf8mb4;   -- кодировка соединения, иначе вместо букв будут «????»

-- 1. Проверка кодировки: кириллица должна отображаться без «????»
SELECT id, last_name, first_name, middle_name
FROM employees
WHERE last_name LIKE 'Х%' OR last_name LIKE 'Дж%' OR last_name LIKE 'Ю%' OR last_name LIKE 'Ш%';

-- 2. Список сотрудников с кафедрой и должностью (JOIN трёх таблиц)
SELECT e.id,
       CONCAT_WS(' ', e.last_name, e.first_name, e.middle_name) AS full_name,
       d.name  AS department,
       p.title AS position
FROM employees e
JOIN departments d ON d.id = e.department_id
JOIN positions   p ON p.id = e.position_id
ORDER BY e.last_name, e.first_name;

-- 3. Сотрудники одной кафедры (параметр — id кафедры)
SELECT last_name, first_name, hire_date
FROM employees
WHERE department_id = 1 AND status <> 'dismissed'
ORDER BY last_name;

-- 4. Поиск по части фамилии (так работает поиск в приложении)
SELECT id, last_name, first_name
FROM employees
WHERE last_name LIKE CONCAT('%', 'хим', '%');
-- Обратите внимание: collation utf8mb4_unicode_ci не различает регистр.

-- 5. Кафедры с факультетом и ФИО заведующего (LEFT JOIN — кафедра может быть без заведующего)
SELECT f.name AS faculty, d.name AS department,
       CONCAT(h.last_name, ' ', LEFT(h.first_name, 1), '.') AS head
FROM departments d
JOIN faculties f      ON f.id = d.faculty_id
LEFT JOIN employees h ON h.id = d.head_employee_id
ORDER BY f.name, d.name;

-- 6. Отчёт «штат по кафедрам» (GROUP BY + условная агрегация)
SELECT d.name AS department,
       COUNT(e.id)                                    AS total,
       SUM(e.academic_degree <> 'none')               AS with_degree,
       SUM(e.status = 'on_leave')                     AS on_leave
FROM departments d
LEFT JOIN employees e ON e.department_id = d.id AND e.status <> 'dismissed'
GROUP BY d.id, d.name
ORDER BY d.name;

-- 7. Количество заявок по статусам
SELECT status, COUNT(*) AS cnt
FROM leave_requests
GROUP BY status;

-- 8. Заявки, ожидающие решения заведующего кафедрой ПИС (id = 1)
SELECT lr.id, e.last_name, lr.type, lr.start_date, lr.end_date,
       DATEDIFF(lr.end_date, lr.start_date) + 1 AS days
FROM leave_requests lr
JOIN employees e ON e.id = lr.employee_id
WHERE lr.status = 'pending' AND e.department_id = 1
ORDER BY lr.created_at;

-- 9. Кто принял решение по заявке (два JOIN к одной таблице users — нужны псевдонимы)
SELECT lr.id, lr.status, uh.username AS head_user, uk.username AS hr_user
FROM leave_requests lr
LEFT JOIN users uh ON uh.id = lr.head_decision_by
LEFT JOIN users uk ON uk.id = lr.hr_decision_by;

-- 10. Средний стаж (в годах) по категориям должностей
SELECT p.category, ROUND(AVG(TIMESTAMPDIFF(YEAR, e.hire_date, CURDATE())), 1) AS avg_years
FROM employees e
JOIN positions p ON p.id = e.position_id
WHERE e.status <> 'dismissed'
GROUP BY p.category;

-- 11. Проверка ограничения CHECK: этот INSERT должен завершиться ошибкой
-- INSERT INTO leave_requests (employee_id, type, start_date, end_date)
-- VALUES (2, 'annual', '2026-12-10', '2026-12-01');

-- 12. Проверка внешнего ключа: удалить кафедру с сотрудниками нельзя (ошибка 1451)
-- DELETE FROM departments WHERE id = 1;

-- 13. План выполнения: используется ли индекс idx_employees_last_name?
EXPLAIN SELECT * FROM employees WHERE last_name = 'Рахимов';
