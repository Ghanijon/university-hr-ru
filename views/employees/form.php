<?php
/**
 * Форма создания и редактирования сотрудника.
 * @var \App\Core\View $this
 * @var array    $employee     значения полей
 * @var array    $errors       ошибки проверки: поле => текст
 * @var int|null $id           null — новый сотрудник
 * @var array    $departments
 * @var array    $positions
 */
$action = $id ? '/employees/' . $id . '/edit' : '/employees/create';
$val = fn (string $field): string => $this->e($employee[$field] ?? '');
// Классы и сообщение об ошибке для поля
$invalid = fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
$error = function (string $field) use ($errors): string {
    return isset($errors[$field])
        ? '<div class="invalid-feedback" id="' . $field . '-error">' . $this->e($errors[$field]) . '</div>'
        : '';
};
?>
<h1 class="mb-4"><?= $id ? 'Редактирование сотрудника' : 'Новый сотрудник' ?></h1>

<?php if ($errors): ?>
  <div class="alert alert-danger" role="alert">Проверьте форму: найдено ошибок — <?= count($errors) ?>.</div>
<?php endif; ?>

<form class="card card-body" method="post" action="<?= $this->url($action) ?>">
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label" for="last_name">Фамилия *</label>
      <input class="form-control<?= $invalid('last_name') ?>" id="last_name" name="last_name" required maxlength="60"
             value="<?= $val('last_name') ?>">
      <?= $error('last_name') ?>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="first_name">Имя *</label>
      <input class="form-control<?= $invalid('first_name') ?>" id="first_name" name="first_name" required maxlength="60"
             value="<?= $val('first_name') ?>">
      <?= $error('first_name') ?>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="middle_name">Отчество</label>
      <input class="form-control<?= $invalid('middle_name') ?>" id="middle_name" name="middle_name" maxlength="60"
             value="<?= $val('middle_name') ?>">
      <?= $error('middle_name') ?>
    </div>

    <div class="col-md-4">
      <label class="form-label" for="birth_date">Дата рождения *</label>
      <input class="form-control<?= $invalid('birth_date') ?>" type="date" id="birth_date" name="birth_date" required
             value="<?= $val('birth_date') ?>">
      <?= $error('birth_date') ?>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="gender">Пол *</label>
      <select class="form-select<?= $invalid('gender') ?>" id="gender" name="gender" required>
        <option value="">— выберите —</option>
        <?php foreach ($this->labels('gender') as $value => $text): ?>
          <option value="<?= $value ?>" <?= ($employee['gender'] ?? '') === $value ? 'selected' : '' ?>><?= $this->e($text) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $error('gender') ?>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="academic_degree">Учёная степень</label>
      <select class="form-select<?= $invalid('academic_degree') ?>" id="academic_degree" name="academic_degree">
        <?php foreach ($this->labels('degree') as $value => $text): ?>
          <option value="<?= $value ?>" <?= ($employee['academic_degree'] ?? 'none') === $value ? 'selected' : '' ?>><?= $this->e($text) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $error('academic_degree') ?>
    </div>

    <div class="col-md-6">
      <label class="form-label" for="phone">Телефон</label>
      <input class="form-control<?= $invalid('phone') ?>" type="tel" id="phone" name="phone" maxlength="20" placeholder="+992 92 123 45 67" pattern="[0-9+\(\)\s\-]{5,20}"
             value="<?= $val('phone') ?>">
      <?= $error('phone') ?>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="email">E-mail</label>
      <input class="form-control<?= $invalid('email') ?>" type="email" id="email" name="email" maxlength="120"
             placeholder="name@example.tj" value="<?= $val('email') ?>">
      <?= $error('email') ?>
    </div>

    <div class="col-md-6">
      <label class="form-label" for="department_id">Кафедра *</label>
      <select class="form-select<?= $invalid('department_id') ?>" id="department_id" name="department_id" required>
        <option value="">— выберите кафедру —</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= (int) $d['id'] ?>" <?= (string) ($employee['department_id'] ?? '') === (string) $d['id'] ? 'selected' : '' ?>>
            <?= $this->e($d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?= $error('department_id') ?>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="position_id">Должность *</label>
      <select class="form-select<?= $invalid('position_id') ?>" id="position_id" name="position_id" required>
        <option value="">— выберите должность —</option>
        <?php foreach ($positions as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= (string) ($employee['position_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>>
            <?= $this->e($p['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?= $error('position_id') ?>
    </div>

    <div class="col-md-6">
      <label class="form-label" for="hire_date">Дата приёма *</label>
      <input class="form-control<?= $invalid('hire_date') ?>" type="date" id="hire_date" name="hire_date" required
             value="<?= $val('hire_date') ?>">
      <?= $error('hire_date') ?>
    </div>
    <div class="col-md-6">
      <label class="form-label" for="status">Статус *</label>
      <select class="form-select<?= $invalid('status') ?>" id="status" name="status" required>
        <?php foreach ($this->labels('employee_status') as $value => $text): ?>
          <option value="<?= $value ?>" <?= ($employee['status'] ?? 'active') === $value ? 'selected' : '' ?>><?= $this->e($text) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $error('status') ?>
    </div>
  </div>

  <div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Сохранить</button>
    <a class="btn btn-outline-secondary" href="<?= $this->url($id ? '/employees/' . $id : '/employees') ?>">Отмена</a>
  </div>
</form>
