<?php
/** Сообщение об успехе или ошибке. @var \App\Core\View $this  @var array{0: string, 1: string}|null $notice */
if (!empty($notice)): [$type, $text] = $notice; ?>
  <div class="alert alert-<?= $this->e($type) ?> alert-dismissible fade show" role="alert">
    <?= $this->e($text) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
  </div>
<?php endif; ?>
