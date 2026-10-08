<?php
/**
 * Справочники: кафедры и должности.
 * @var \App\Core\View $this
 * @var array $departments
 * @var array $positions
 */
?>
<h1 class="mb-4">Кафедры и должности</h1>

<div class="row g-4">
  <section class="col-12 col-xl-8">
    <div class="card">
      <div class="card-header">Кафедры (<?= count($departments) ?>)</div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr><th scope="col">Кафедра</th><th scope="col">Факультет</th><th scope="col">Заведующий</th><th scope="col" class="text-end">Сотрудников</th></tr>
          </thead>
          <tbody>
            <?php foreach ($departments as $d): ?>
              <tr>
                <td><a href="<?= $this->url('/employees?department_id=' . (int) $d['id']) ?>"><?= $this->e($d['name']) ?></a></td>
                <td><?= $this->e($d['faculty_name']) ?></td>
                <td><?= $d['head_name'] ? $this->e($d['head_name']) : '<span class="text-muted">не назначен</span>' ?></td>
                <td class="text-end"><?= (int) $d['employees_count'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="col-12 col-xl-4">
    <div class="card">
      <div class="card-header">Должности (<?= count($positions) ?>)</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($positions as $p): ?>
          <li class="list-group-item d-flex justify-content-between gap-2">
            <span><?= $this->e($p['title']) ?></span>
            <small class="text-muted text-end"><?= $this->e($this->label('category', $p['category'])) ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
</div>
