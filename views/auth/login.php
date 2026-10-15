<?php
/**
 * Форма входа. Выводится без общего меню (layout = 'auth/layout').
 * Тестовые логины: admin, hr, head_pis, teacher1 (пароль Secret123!)
 * @var \App\Core\View $this
 * @var string|null $error
 * @var string $username
 */
?>
<div class="login-page">
  <div class="card login-card">
    <div class="card-body p-4">
      <div class="login-logo" aria-hidden="true">HR</div>
      <h1 class="h2 text-center mb-1">Кадровая система</h1>
      <p class="text-center text-muted mb-4">Политехнический институт · учебный проект</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= $this->e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= $this->url('/login') ?>">
        <?= \App\Core\Csrf::field() ?>
        <div class="mb-3">
          <label class="form-label" for="username">Логин</label>
          <input class="form-control" id="username" name="username" required maxlength="50" autocomplete="username"
                 autofocus value="<?= $this->e($username ?? '') ?>" data-label="Логин">
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Пароль</label>
          <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password"
                 data-label="Пароль">
        </div>
        <button class="btn btn-primary w-100" type="submit">Войти</button>
      </form>
    </div>
  </div>
</div>
