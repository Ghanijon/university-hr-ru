<?php
/**
 * Главная страница.
 * @var \App\Core\View $this
 * @var array $user
 * @var array $stats         [[подпись, число], ...]
 * @var array $latestLeaves
 */
$greeting = $user['full_name'] ?: $user['username'];
$cols = count($stats) === 4 ? 'col-xl-3' : 'col-xl-4';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
  <div class="me-auto">
    <h1 class="mb-0">Добро пожаловать, <?= $this->e($greeting) ?></h1>
    <p class="text-muted mb-0"><?= $this->e($this->label('role', $user['role'])) ?> · <?= date('d.m.Y') ?></p>
  </div>
  <a class="btn btn-outline-primary" href="<?= $this->url('/leaves/create') ?>">Подать заявку на отпуск</a>
  <?php if (in_array($user['role'], ['admin', 'hr'], true)): ?>
    <a class="btn btn-primary" href="<?= $this->url('/employees/create') ?>">+ Новый сотрудник</a>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($stats as [$label, $number]): ?>
    <div class="col-12 col-sm-6 <?= $cols ?>">
      <div class="card stat-card h-100"><div class="card-body">
        <div class="stat-number"><?= (int) $number ?></div>
        <div class="stat-label"><?= $this->e($label) ?></div>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>

<section class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><?= $user['role'] === 'employee' ? 'Мои заявки на отпуск' : 'Последние заявки на отпуск' ?></span>
    <a class="small" href="<?= $this->url('/leaves') ?>">Все заявки →</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr><th scope="col">Сотрудник</th><th scope="col">Тип</th><th scope="col">Период</th><th scope="col">Статус</th></tr>
      </thead>
      <tbody>
        <?php foreach ($latestLeaves as $l): ?>
          <tr>
            <td><?= $this->e($l['last_name'] . ' ' . $l['first_name']) ?></td>
            <td><?= $this->e($this->label('leave_type', $l['type'])) ?></td>
            <td class="text-nowrap"><?= $this->date($l['start_date']) ?> – <?= $this->date($l['end_date']) ?></td>
            <td><span class="badge status status-<?= $this->e($l['status']) ?>"><?= $this->e($this->label('leave_status', $l['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($latestLeaves === []): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Заявок пока нет</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
