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
