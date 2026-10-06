// public/assets/js/app.js — интерактивность University HR
// Подключается как ES-модуль: <script type="module" src="/assets/js/app.js"></script>
// Зависимости: только Bootstrap 5.3 (bootstrap.bundle.min.js подключён раньше, объект window.bootstrap).

/* ---------- 1. Общие утилиты ---------- */

// Откладывает вызов fn, пока пользователь не сделает паузу delay мс.
function debounce(fn, delay = 300) {
  let timerId;
  return (...args) => {
    clearTimeout(timerId);
    timerId = setTimeout(() => fn(...args), delay);
  };
}

// 'YYYY-MM-DD' -> 'DD.MM.YYYY' (пустое значение -> '')
function formatDate(isoDate) {
  if (!isoDate) return '';
  const [year, month, day] = isoDate.split('-');
  return `${day}.${month}.${year}`;
}

/* ---------- 2. Валидация формы заявки на отпуск ---------- */

const FIELD_MESSAGES = {
  type: { valueMissing: 'Выберите тип отпуска.' },
  start_date: { valueMissing: 'Укажите дату начала отпуска.' },
  end_date: { valueMissing: 'Укажите дату окончания отпуска.' },
};

// Текст ошибки для поля на основе его ValidityState ('' — ошибок нет).
function messageFor(field) {
  const v = field.validity;
  if (v.valid) return '';
  if (v.customError) return field.validationMessage; // наше правило через setCustomValidity()
  const custom = FIELD_MESSAGES[field.name] ?? {};
  if (v.valueMissing) return custom.valueMissing ?? 'Заполните это поле.';
  if (v.badInput) return 'Введите корректное значение.';
  if (v.tooLong) return `Не более ${field.maxLength} символов.`;
  if (v.rangeUnderflow) return 'Значение меньше допустимого.';
  if (v.typeMismatch) return 'Неверный формат.';
  return 'Проверьте значение поля.';
}

// Показывает/скрывает ошибку у поля. Возвращает true, если поле корректно.
function showFieldState(field) {
  const message = messageFor(field);
  const feedback = document.getElementById(`${field.id}-error`);
  field.classList.toggle('is-invalid', message !== '');
  if (message) {
    field.setAttribute('aria-invalid', 'true');
  } else {
    field.removeAttribute('aria-invalid');
  }
  if (feedback) feedback.textContent = message;
  return message === '';
}

// Наше правило, которого нет в HTML: дата окончания не раньше даты начала.
function checkLeaveDates(form) {
  const start = form.elements.start_date;
  const end = form.elements.end_date;
  end.setCustomValidity(''); // сбрасываем прошлую ошибку, иначе поле останется невалидным навсегда
  // Значения <input type="date"> всегда в формате YYYY-MM-DD, поэтому их можно сравнивать как строки.
  if (start.value && end.value && end.value < start.value) {
    end.setCustomValidity('Дата окончания не может быть раньше даты начала.');
  }
}

function initLeaveForm() {
  const form = document.getElementById('leave-form');
  if (!form) return; // на этой странице формы нет

  const { start_date: start, end_date: end } = form.elements;
  const fields = () => form.querySelectorAll('input, select, textarea');

  // Подсказка для календаря: раньше даты начала выбрать нельзя
  start.addEventListener('change', () => {
    end.min = start.value;
  });

  const refresh = (field) => {
    checkLeaveDates(form);
    showFieldState(field);
    // Изменение даты начала может «починить» или «сломать» дату окончания
    if (field !== end && end.classList.contains('is-invalid')) showFieldState(end);
  };

  // focusout всплывает (в отличие от blur) — один обработчик на всю форму
  form.addEventListener('focusout', (event) => {
    if (event.target.matches('input, select, textarea')) refresh(event.target);
  });

  // Уже помеченное поле перепроверяем при каждом вводе: ошибка исчезает сразу после исправления
  form.addEventListener('input', (event) => {
    if (event.target.classList.contains('is-invalid')) refresh(event.target);
  });

  form.addEventListener('submit', (event) => {
    checkLeaveDates(form);
    let firstInvalid = null;
    fields().forEach((field) => {
      if (!showFieldState(field) && !firstInvalid) firstInvalid = field;
    });
    if (firstInvalid) {
      event.preventDefault(); // не отправляем форму с ошибками
      firstInvalid.focus();
    }
  });
}

/* ---------- 3. Живой поиск сотрудников ---------- */

const STATUS_LABELS = {
  active: 'Работает',
  on_leave: 'В отпуске',
  dismissed: 'Уволен',
};

function createCell(text, className = '') {
  const td = document.createElement('td');
  td.textContent = text ?? ''; // textContent: любые < > & из данных выводятся как текст
  if (className) td.className = className;
  return td;
}

function createEmployeeRow(employee) {
  const tr = document.createElement('tr');

  const nameCell = document.createElement('td');
  const link = document.createElement('a');
  link.href = `/employees/${encodeURIComponent(employee.id)}`;
  link.textContent = employee.full_name;
  nameCell.append(link);

  const statusCell = document.createElement('td');
  const badge = document.createElement('span');
  badge.className = 'badge badge-status';
  if (employee.status in STATUS_LABELS) badge.classList.add(`status-${employee.status}`);
  badge.textContent = STATUS_LABELS[employee.status] ?? employee.status;
  statusCell.append(badge);

  const actionsCell = document.createElement('td');
  actionsCell.className = 'text-end text-nowrap';
  const editLink = document.createElement('a');
  editLink.className = 'btn btn-sm btn-outline-secondary';
  editLink.href = `/employees/${encodeURIComponent(employee.id)}/edit`;
  editLink.textContent = 'Изменить';
  const deleteButton = document.createElement('button');
  deleteButton.type = 'button';
  deleteButton.className = 'btn btn-sm btn-outline-danger ms-1';
  deleteButton.dataset.deleteUrl = `/employees/${encodeURIComponent(employee.id)}/delete`;
  deleteButton.dataset.employeeName = employee.full_name;
  deleteButton.textContent = 'Удалить';
  actionsCell.append(editLink, deleteButton);

  tr.append(
    nameCell,
    createCell(employee.department),
    createCell(employee.position, 'd-none d-sm-table-cell'),
    createCell(employee.phone, 'd-none d-md-table-cell'),
    createCell(formatDate(employee.hire_date), 'd-none d-lg-table-cell'),
    statusCell,
    actionsCell,
  );
  return tr;
}

function renderEmployees(tbody, employees) {
  if (employees.length === 0) {
    const tr = document.createElement('tr');
    const td = createCell('Сотрудники не найдены. Измените условия поиска.', 'text-center text-body-secondary py-4');
    td.colSpan = 7;
    tr.append(td);
    tbody.replaceChildren(tr);
    return;
  }
  tbody.replaceChildren(...employees.map(createEmployeeRow));
}

function initEmployeeSearch() {
  const input = document.getElementById('employee-search');
  const tbody = document.getElementById('employees-tbody');
  if (!input || !tbody) return;

  const statusBox = document.getElementById('employee-search-status');
  const setStatus = (text) => {
    if (statusBox) statusBox.textContent = text;
  };
  let controller = null; // AbortController текущего запроса

  async function search(query) {
    controller?.abort(); // отменяем предыдущий запрос, если он ещё не завершился
    controller = new AbortController();
    tbody.setAttribute('aria-busy', 'true');
    setStatus('Поиск…');
    try {
      const response = await fetch(`/api/employees?q=${encodeURIComponent(query)}`, {
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`); // fetch не бросает ошибку на 404/500
      const employees = await response.json();
      renderEmployees(tbody, employees);
      setStatus(employees.length ? `Найдено сотрудников: ${employees.length}` : 'Сотрудники не найдены.');
    } catch (error) {
      if (error.name === 'AbortError') return; // запрос отменён более новым — это нормально
      console.error('Ошибка живого поиска:', error);
      setStatus('Не удалось выполнить поиск. Проверьте соединение и попробуйте ещё раз.');
    } finally {
      tbody.removeAttribute('aria-busy');
    }
  }

  const debouncedSearch = debounce(search, 300);
  input.addEventListener('input', () => debouncedSearch(input.value.trim()));
}

/* ---------- 4. Модальное подтверждение удаления ---------- */

function initDeleteConfirm() {
  const modalElement = document.getElementById('confirm-delete-modal');
  if (!modalElement || !window.bootstrap) return;

  const form = document.getElementById('confirm-delete-form');
  const nameElement = document.getElementById('confirm-delete-name');
  const submitButton = form.querySelector('[type="submit"]');
  const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
  let lastTrigger = null;

  // Делегирование: один обработчик на документ работает и для строк, добавленных живым поиском
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-delete-url]');
    if (!button) return;
    lastTrigger = button;
    form.action = button.dataset.deleteUrl;
    nameElement.textContent = button.dataset.employeeName ?? '';
    submitButton.disabled = false;
    modal.show();
  });

  // После закрытия возвращаем фокус на кнопку, с которой начали (доступность)
  modalElement.addEventListener('hidden.bs.modal', () => {
    if (lastTrigger?.isConnected) lastTrigger.focus();
  });

  // Защита от двойного нажатия «Удалить»
  form.addEventListener('submit', () => {
    submitButton.disabled = true;
  });
}

/* ---------- 5. Запуск ---------- */
// Модуль выполняется после разбора HTML (как defer), поэтому DOMContentLoaded не нужен.
initLeaveForm();
initEmployeeSearch();
initDeleteConfirm();
