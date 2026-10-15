<?php
/**
 * Список заявок на отпуск.
 * @var \App\Core\View $this
 * @var array  $leaves
 * @var string $status      текущий фильтр ('' — все)
 * @var array  $counts      число заявок по статусам
 * @var array  $user
 * @var array  $pagination
 */
use App\Core\Csrf;
use App\Services\LeaveRequestRules;

$tabs = ['' => 'Все'] + $this->labels('leave_status');
$showEmployee = $user['role'] !== 'employee';
$showDepartment = in_array($user['role'], ['admin', 'hr'], true);
$canAct = in_array($user['role'], ['admin', 'hr', 'head'], true);
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h1 class="me-auto mb-0">Заявки на отпуск</h1>
  <a class="btn btn-primary" href="<?= $this->url('/leaves/create') ?>">+ Подать заявку</a>
</div>

<ul class="nav nav-pills mb-3 flex-wrap gap-1">
  <?php foreach ($tabs as $value => $text): ?>
    <li class="nav-item">
      <a class="nav-link<?= $value === $status ? ' active' : '' ?>" <?= $value === $status ? 'aria-current="page"' : '' ?>
         href="<?= $this->url('/leaves' . ($value !== '' ? '?status=' . $value : '')) ?>">
        <?= $this->e($text) ?>
        <?php if ($value !== ''): ?><span class="badge text-bg-light ms-1"><?= (int) $counts[$value] ?></span><?php endif; ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card">
  <?php if ($leaves === []): ?>
    <div class="card-body text-center text-muted py-5">Заявок нет</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle table-cards">
        <thead>
          <tr>
            <th scope="col">№</th>
            <?php if ($showEmployee): ?><th scope="col">Сотрудник</th><?php endif; ?>
            <?php if ($showDepartment): ?><th scope="col">Кафедра</th><?php endif; ?>
            <th scope="col">Тип</th>
            <th scope="col">Период</th>
            <th scope="col" class="text-end">Дней</th>
            <th scope="col">Статус</th>
            <?php if ($canAct): ?><th scope="col"><span class="visually-hidden">Действия</span></th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leaves as $l): ?>
            <tr>
              <td data-label="№"><?= (int) $l['id'] ?></td>
              <?php if ($showEmployee): ?>
                <td data-label="Сотрудник">
                  <a href="<?= $this->url('/employees/' . (int) $l['employee_id']) ?>"><?= $this->e($l['last_name'] . ' ' . $l['first_name']) ?></a>
                </td>
              <?php endif; ?>
              <?php if ($showDepartment): ?><td data-label="Кафедра" class="small"><?= $this->e($l['department_name']) ?></td><?php endif; ?>
              <td data-label="Тип"><?= $this->e($this->label('leave_type', $l['type'])) ?></td>
              <td data-label="Период" class="text-nowrap">
                <?= $this->date($l['start_date']) ?> – <?= $this->date($l['end_date']) ?>
                <?php if (!empty($l['reason'])): ?><div class="small text-muted text-wrap"><?= $this->e($l['reason']) ?></div><?php endif; ?>
              </td>
              <td data-label="Дней" class="text-end"><?= (int) $l['days'] ?></td>
              <td data-label="Статус">
                <span class="badge status status-<?= $this->e($l['status']) ?>"><?= $this->e($this->label('leave_status', $l['status'])) ?></span>
              </td>
              <?php if ($canAct): ?>
                <td class="text-end text-nowrap cell-actions">
                  <?php if (LeaveRequestRules::canDecide($l, $user, 'approve')): ?>
                    <form method="post" action="<?= $this->url('/leaves/' . (int) $l['id'] . '/approve') ?>" class="d-inline">
                      <?= Csrf::field() ?>
                      <button class="btn btn-sm btn-success" type="submit">
                        <?= $l['status'] === 'pending' ? 'Согласовать' : 'Утвердить' ?>
                      </button>
                    </form>
                  <?php endif; ?>
                  <?php if (LeaveRequestRules::canDecide($l, $user, 'reject')): ?>
                    <form method="post" action="<?= $this->url('/leaves/' . (int) $l['id'] . '/reject') ?>" class="d-inline">
                      <?= Csrf::field() ?>
                      <button class="btn btn-sm btn-outline-danger" type="submit">Отклонить</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($user['role'] === 'head' && $l['status'] === 'pending' && (int) $l['employee_id'] === (int) $user['employee_id']): ?>
                    <small class="text-muted">Своя заявка — решает администратор</small>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?= $this->partial('pagination', $pagination + ['path' => '/leaves', 'query' => ['status' => $status]]) ?>
