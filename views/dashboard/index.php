<?php
/**
 * Главная страница.
 * @var \App\Core\View $this
 * @var array $stats   ['active' => .., 'on_leave' => .., 'pending' => .., 'departments' => ..]
 */
$cards = [
    ['active', 'Работают'],
    ['on_leave', 'В отпуске'],
    ['pending', 'Заявки ждут решения'],
    ['departments', 'Кафедр'],
];
?>
<h1 class="mb-4">Главная</h1>

<div class="row g-3 mb-4">
  <?php foreach ($cards as [$key, $label]): ?>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card stat-card h-100"><div class="card-body">
        <div class="stat-number"><?= (int) ($stats[$key] ?? 0) ?></div>
        <div class="stat-label"><?= $this->e($label) ?></div>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>

<section class="card">
  <div class="card-header">Последние заявки на отпуск</div>
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
