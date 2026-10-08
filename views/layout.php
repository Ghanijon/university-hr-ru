<?php
/**
 * Общий макет всех страниц.
 * @var \App\Core\View $this
 * @var string $content  HTML конкретной страницы
 * @var string $title    заголовок вкладки браузера
 */
$active = $active ?? '';
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $this->e($title ?? 'Главная') ?> — Кадровая система</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="<?= $this->url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?= $this->partial('nav', ['active' => $active]) ?>

<main class="container py-4">
  <?= $content ?>
</main>

<footer class="app-footer container pb-4 text-center">
  © <?= date('Y') ?> Учебный проект university-hr · все данные вымышлены
</footer>

<?= $this->partial('confirm-delete-modal') ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script type="module" src="<?= $this->url('/assets/js/app.js') ?>"></script>
</body>
</html>
