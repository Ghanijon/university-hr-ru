<?php
/** @var \App\Core\View $this  @var string $active — ключ активного пункта меню */
$items = [
    'dashboard'   => ['/', 'Главная'],
    'employees'   => ['/employees', 'Сотрудники'],
    'departments' => ['/departments', 'Кафедры'],
    'reports'     => ['/reports/staff', 'Отчёт'],
];
?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?= $this->url('/') ?>">ХПИ ТТУ · Кадры</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Открыть меню">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <?php foreach ($items as $key => [$href, $text]): ?>
          <li class="nav-item">
            <a class="nav-link<?= $key === $active ? ' active' : '' ?>"
               <?= $key === $active ? 'aria-current="page"' : '' ?>
               href="<?= $this->url($href) ?>"><?= $this->e($text) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn-sm btn-outline-light" href="<?= $this->url('/login') ?>">Войти</a>
    </div>
  </div>
</nav>
