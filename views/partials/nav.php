<?php
/**
 * Верхнее меню. Пункты зависят от роли пользователя.
 * @var \App\Core\View $this
 * @var string     $active  ключ активного пункта
 * @var array|null $user    текущий пользователь (null — гость)
 */
$role = $user['role'] ?? null;
$items = [];
if ($user !== null) {
    $items['dashboard'] = ['/', 'Главная'];
    if (in_array($role, ['admin', 'hr', 'head'], true)) {
        $items['employees'] = ['/employees', 'Сотрудники'];
    }
    if ($role === 'employee' && !empty($user['employee_id'])) {
        $items['profile'] = ['/employees/' . (int) $user['employee_id'], 'Мой профиль'];
    }
    if (in_array($role, ['admin', 'hr', 'head'], true)) {
        $items['departments'] = ['/departments', 'Кафедры'];
    }
    $items['leaves'] = ['/leaves', 'Отпуска'];
    if (in_array($role, ['admin', 'hr', 'head'], true)) {
        $items['reports'] = ['/reports/staff', 'Отчёт'];
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?= $this->url('/') ?>">ХПИ ТТУ · Кадры</a>
    <?php if ($user !== null): ?>
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
        <div class="d-flex flex-wrap align-items-center gap-2 text-white py-2 py-lg-0">
          <span title="<?= $this->e($user['full_name'] ?? '') ?>"><?= $this->e($user['username']) ?></span>
          <span class="badge role-badge"><?= $this->e($this->label('role', $role)) ?></span>
          <form method="post" action="<?= $this->url('/logout') ?>" class="m-0">
            <?= \App\Core\Csrf::field() ?>
            <button class="btn btn-sm btn-outline-light" type="submit">Выйти</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <a class="btn btn-sm btn-outline-light" href="<?= $this->url('/login') ?>">Войти</a>
    <?php endif; ?>
  </div>
</nav>
