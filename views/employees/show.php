<?php
/**
 * Карточка сотрудника.
 * @var \App\Core\View $this
 * @var array $employee
 * @var array $leaves   заявки сотрудника
 * @var bool  $canEdit
 */
$e = $employee;
$fullName = trim($e['last_name'] . ' ' . $e['first_name'] . ' ' . ($e['middle_name'] ?? ''));
?>
<nav aria-label="Навигационная цепочка">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $this->url('/employees') ?>">Сотрудники</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= $this->e($fullName) ?></li>
  </ol>
</nav>

<div class="row g-4">
  <aside class="col-12 col-lg-3">
    <div class="card"><div class="card-body text-center">
      <?php if (!empty($e['photo_path'])): ?>
        <img class="avatar avatar-lg mx-auto mb-3 d-flex" src="<?= $this->e($this->url('/' . $e['photo_path'])) ?>"
             alt="Фото: <?= $this->e($fullName) ?>">
      <?php else: ?>
        <span class="avatar avatar-lg mx-auto mb-3" role="img" aria-label="Фото отсутствует">
          <?= $this->e($this->initials($e['last_name'], $e['first_name'])) ?>
        </span>
      <?php endif; ?>
      <h2 class="h3"><?= $this->e($fullName) ?></h2>
      <span class="badge status status-<?= $this->e($e['status']) ?>"><?= $this->e($this->label('employee_status', $e['status'])) ?></span>
    </div></div>
  </aside>

  <section class="col-12 col-lg-9">
    <div class="card mb-4">
      <div class="card-header">Служебные данные</div>
      <div class="card-body">
        <dl class="details">
          <dt>Кафедра</dt><dd><?= $this->e($e['department_name']) ?></dd>
          <dt>Факультет</dt><dd><?= $this->e($e['faculty_name']) ?></dd>
          <dt>Должность</dt><dd><?= $this->e($e['position_title']) ?></dd>
          <dt>Учёная степень</dt><dd><?= $this->e($this->label('degree', $e['academic_degree'])) ?></dd>
          <dt>Дата приёма</dt><dd><?= $this->date($e['hire_date']) ?></dd>
        </dl>
      </div>
    </div>
    <div class="card mb-4">
      <div class="card-header">Личные данные</div>
      <div class="card-body">
        <dl class="details">
          <dt>Дата рождения</dt><dd><?= $this->date($e['birth_date']) ?></dd>
          <dt>Пол</dt><dd><?= $this->e($this->label('gender', $e['gender'])) ?></dd>
          <dt>Телефон</dt><dd><?= $e['phone'] ? $this->e($e['phone']) : '—' ?></dd>
          <dt>E-mail</dt>
          <dd><?= $e['email'] ? '<a href="mailto:' . $this->e($e['email']) . '">' . $this->e($e['email']) . '</a>' : '—' ?></dd>
        </dl>
      </div>
    </div>

    <?php if ($canEdit): ?>
      <div class="d-flex gap-2 mb-4 no-print">
        <a class="btn btn-primary" href="<?= $this->url('/employees/' . (int) $e['id'] . '/edit') ?>">Редактировать</a>
      </div>
    <?php endif; ?>
  </section>
</div>
