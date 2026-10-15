<?php
/**
 * Список сотрудников.
 * @var \App\Core\View $this
 * @var array $employees    строки из БД (с названиями кафедры и должности)
 * @var array $departments  для фильтра
 * @var array $filters      ['q' => ..., 'department_id' => ..., 'status' => ...]
 * @var array $pagination   ['page' => 1, 'pages' => 5, 'total' => 46]
 * @var bool  $canEdit      можно ли добавлять сотрудников (admin, hr)
 */
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="me-auto mb-0">Сотрудники</h1>
  <?php if ($canEdit): ?>
    <a class="btn btn-primary" href="<?= $this->url('/employees/create') ?>">+ Добавить</a>
  <?php endif; ?>
</div>

<form class="card card-body mb-3 no-print" method="get" action="<?= $this->url('/employees') ?>" role="search">
  <div class="row g-2">
    <div class="col-12 col-md-5">
      <label class="visually-hidden" for="employee-search">Поиск по ФИО</label>
      <!-- Живой поиск (app.js): при вводе таблица ниже перерисовывается данными из /api/employees -->
      <input class="form-control" type="search" id="employee-search" name="q" placeholder="Поиск по ФИО…"
             autocomplete="off" value="<?= $this->e($filters['q']) ?>">
    </div>
    <div class="col-6 col-md-3">
      <label class="visually-hidden" for="department_id">Кафедра</label>
      <select class="form-select" id="department_id" name="department_id">
        <?php if (count($departments) > 1): ?><option value="">Все кафедры</option><?php endif; ?>
        <?php foreach ($departments as $d): ?>
          <option value="<?= (int) $d['id'] ?>" <?= (string) $d['id'] === (string) $filters['department_id'] ? 'selected' : '' ?>>
            <?= $this->e($d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="visually-hidden" for="status">Статус</label>
      <select class="form-select" id="status" name="status">
        <option value="">Все статусы</option>
        <?php foreach ($this->labels('employee_status') as $value => $text): ?>
          <option value="<?= $value ?>" <?= $value === $filters['status'] ? 'selected' : '' ?>><?= $this->e($text) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-2 d-grid">
      <button class="btn btn-outline-primary" type="submit">Найти</button>
    </div>
  </div>
</form>
<p id="employee-search-status" class="small text-muted no-print" aria-live="polite"></p>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle table-cards">
      <thead>
        <tr>
          <th scope="col">ФИО</th>
          <th scope="col">Кафедра</th>
          <th scope="col" class="d-none d-sm-table-cell">Должность</th>
          <th scope="col" class="d-none d-md-table-cell">Телефон</th>
          <th scope="col" class="d-none d-lg-table-cell">Дата приёма</th>
          <th scope="col">Статус</th>
          <th scope="col"><span class="visually-hidden">Действия</span></th>
        </tr>
      </thead>
      <tbody id="employees-tbody">
        <?php foreach ($employees as $e): ?>
          <?php $fullName = trim($e['last_name'] . ' ' . $e['first_name'] . ' ' . ($e['middle_name'] ?? '')); ?>
          <tr>
            <td data-label="ФИО">
              <div class="d-flex align-items-center gap-2">
                <?php if (!empty($e['photo_path'])): ?>
                  <img class="avatar" src="<?= $this->e($this->url('/' . $e['photo_path'])) ?>" alt="">
                <?php else: ?>
                  <span class="avatar" aria-hidden="true"><?= $this->e($this->initials($e['last_name'], $e['first_name'])) ?></span>
                <?php endif; ?>
                <a href="<?= $this->url('/employees/' . (int) $e['id']) ?>"><?= $this->e($fullName) ?></a>
              </div>
            </td>
            <td data-label="Кафедра"><?= $this->e($e['department_name']) ?></td>
            <td data-label="Должность" class="d-none d-sm-table-cell"><?= $this->e($e['position_title']) ?></td>
            <td data-label="Телефон" class="d-none d-md-table-cell"><?= $this->e($e['phone'] ?? '') ?></td>
            <td data-label="Дата приёма" class="d-none d-lg-table-cell"><?= $this->date($e['hire_date']) ?></td>
            <td data-label="Статус">
              <span class="badge badge-status status-<?= $this->e($e['status']) ?>"><?= $this->e($this->label('employee_status', $e['status'])) ?></span>
            </td>
            <td class="text-end text-nowrap cell-actions">
              <?php if ($canEdit): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= $this->url('/employees/' . (int) $e['id'] . '/edit') ?>">Изменить</a>
                <button type="button" class="btn btn-sm btn-outline-danger ms-1"
                        data-delete-url="<?= $this->url('/employees/' . (int) $e['id'] . '/delete') ?>"
                        data-employee-name="<?= $this->e($fullName) ?>">Удалить</button>
              <?php else: ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= $this->url('/employees/' . (int) $e['id']) ?>">Открыть</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if ($employees === []): ?>
          <tr><td colspan="7" class="text-center text-body-secondary py-4">Сотрудники не найдены. Измените условия поиска.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->partial('pagination', $pagination + ['path' => '/employees', 'query' => $filters]) ?>
