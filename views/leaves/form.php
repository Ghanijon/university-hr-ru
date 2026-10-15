<?php
/**
 * Форма заявки на отпуск. Id полей и блоков ошибок совпадают с лекцией 11:
 * app.js проверяет форму #leave-form до отправки, сервер — после.
 * @var \App\Core\View $this
 * @var array $values
 * @var array $errors
 * @var bool  $onBehalf   ОК и администратор выбирают сотрудника из списка
 * @var array $employees
 * @var array $user
 */
$val = fn (string $field): string => $this->e($values[$field] ?? '');
$invalid = fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
// Блок ошибки выводим всегда (пустой) — app.js заполняет его по id «<id поля>-error»
$error = fn (string $field, string $id): string =>
    '<div class="invalid-feedback" id="' . $id . '-error">' . $this->e($errors[$field] ?? '') . '</div>';
?>
<div class="mx-auto" style="max-width: 720px">
  <h1 class="mb-4">Новая заявка на отпуск</h1>

  <form id="leave-form" class="card card-body" method="post" action="<?= $this->url('/leaves/create') ?>" novalidate>
    <?= \App\Core\Csrf::field() ?>

    <div class="mb-3">
      <label class="form-label" for="employee-id">Сотрудник</label>
      <?php if ($onBehalf): ?>
        <select class="form-select<?= $invalid('employee_id') ?>" id="employee-id" name="employee_id" required
                aria-describedby="employee-id-error">
          <option value="">— выберите сотрудника —</option>
          <?php foreach ($employees as $e): ?>
            <option value="<?= (int) $e['id'] ?>" <?= (string) ($values['employee_id'] ?? '') === (string) $e['id'] ? 'selected' : '' ?>>
              <?= $this->e($e['full_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Отдел кадров может зарегистрировать бумажное заявление за сотрудника</div>
      <?php else: ?>
        <input class="form-control<?= $invalid('employee_id') ?>" id="employee-id" value="<?= $this->e($user['full_name']) ?> (вы)" disabled>
      <?php endif; ?>
      <?= $error('employee_id', 'employee-id') ?>
    </div>

    <div class="mb-3">
      <label class="form-label" for="leave-type">Тип отпуска *</label>
      <select class="form-select<?= $invalid('type') ?>" id="leave-type" name="type" required aria-describedby="leave-type-error">
        <option value="">Выберите тип</option>
        <?php foreach ($this->labels('leave_type') as $value => $text): ?>
          <option value="<?= $value ?>" <?= ($values['type'] ?? '') === $value ? 'selected' : '' ?>><?= $this->e($text) ?></option>
        <?php endforeach; ?>
      </select>
      <?= $error('type', 'leave-type') ?>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-sm-6">
        <label class="form-label" for="start-date">Дата начала *</label>
        <input class="form-control<?= $invalid('start_date') ?>" type="date" id="start-date" name="start_date" required
               min="<?= date('Y-m-d') ?>" value="<?= $val('start_date') ?>" aria-describedby="start-date-error">
        <?= $error('start_date', 'start-date') ?>
      </div>
      <div class="col-sm-6">
        <label class="form-label" for="end-date">Дата окончания *</label>
        <input class="form-control<?= $invalid('end_date') ?>" type="date" id="end-date" name="end_date" required
               value="<?= $val('end_date') ?>" aria-describedby="end-date-error">
        <?= $error('end_date', 'end-date') ?>
      </div>
    </div>
    <p class="form-text mb-3">Ежегодный отпуск — не более 56 календарных дней.</p>

    <div class="mb-3">
      <label class="form-label" for="reason">Причина / комментарий</label>
      <textarea class="form-control<?= $invalid('reason') ?>" id="reason" name="reason" rows="3" maxlength="255"
                aria-describedby="reason-error"><?= $val('reason') ?></textarea>
      <?= $error('reason', 'reason') ?>
    </div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary" type="submit">Отправить на согласование</button>
      <a class="btn btn-outline-secondary" href="<?= $this->url('/leaves') ?>">Отмена</a>
    </div>
  </form>
</div>
