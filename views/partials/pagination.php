<?php
/**
 * Пагинация.
 * @var \App\Core\View $this
 * @var string $path   адрес списка, например '/employees'
 * @var int    $page   текущая страница (с 1)
 * @var int    $pages  всего страниц
 * @var int    $total  всего записей
 * @var array  $query  текущие фильтры — сохраняем их в ссылках
 */
$link = function (int $p) use ($path, $query): string {
    $params = array_filter($query + ['page' => $p], fn ($v) => $v !== '' && $v !== null);
    if ($p === 1) {
        unset($params['page']);
    }

    return $this->url($path) . ($params ? '?' . http_build_query($params) : '');
};
// Показываем не больше 7 номеров: текущая страница ± 3
$from = max(1, $page - 3);
$to = min($pages, $page + 3);
?>
<nav class="mt-3 d-flex flex-column align-items-center gap-1" aria-label="Страницы">
  <?php if ($pages > 1): ?>
    <ul class="pagination mb-0 flex-wrap justify-content-center">
      <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
        <a class="page-link" href="<?= $this->e($link(max(1, $page - 1))) ?>" aria-label="Предыдущая">‹</a>
      </li>
      <?php for ($p = $from; $p <= $to; $p++): ?>
        <li class="page-item<?= $p === $page ? ' active' : '' ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>>
          <a class="page-link" href="<?= $this->e($link($p)) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item<?= $page >= $pages ? ' disabled' : '' ?>">
        <a class="page-link" href="<?= $this->e($link(min($pages, $page + 1))) ?>" aria-label="Следующая">›</a>
      </li>
    </ul>
  <?php endif; ?>
  <small class="text-muted">Найдено: <?= (int) $total ?></small>
</nav>
