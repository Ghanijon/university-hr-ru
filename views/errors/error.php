<?php /** @var \App\Core\View $this  @var int $code  @var string $title  @var string $message */ ?>
<div class="text-center py-5">
  <p class="display-4 fw-semibold text-muted mb-2"><?= (int) ($code ?? 500) ?></p>
  <h1><?= $this->e($title ?? 'Ошибка') ?></h1>
  <?php if (!empty($message)): ?>
    <p class="text-muted"><?= $this->e($message) ?></p>
  <?php endif; ?>
  <a class="btn btn-primary mt-3" href="<?= $this->url('/') ?>">На главную</a>
</div>
