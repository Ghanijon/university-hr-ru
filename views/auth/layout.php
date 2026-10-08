<?php /** Упрощённый макет для страницы входа: без меню. @var \App\Core\View $this */ ?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $this->e($title ?? 'Вход') ?> — Кадровая система</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="<?= $this->url('/assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?= $content ?>
<script type="module" src="<?= $this->url('/assets/js/app.js') ?>"></script>
</body>
</html>
