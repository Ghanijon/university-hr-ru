<?php
/**
 * Отчёт «штат по кафедрам».
 * @var \App\Core\View $this
 * @var array $rows
 * @var array $totals
 */
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-1">
  <h1 class="me-auto mb-0">Штат по кафедрам</h1>
  <button class="btn btn-outline-primary no-print" type="button" onclick="window.print()">Печать</button>
</div>
<p class="text-muted mb-4">Сформирован: <?= date('d.m.Y H:i') ?> · уволенные сотрудники не учитываются</p>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th scope="col">Факультет</th>
          <th scope="col">Кафедра</th>
          <th scope="col" class="text-end">Всего</th>
          <th scope="col" class="text-end">ППС</th>
          <th scope="col" class="text-end">Со степенью</th>
          <th scope="col" class="text-end">В отпуске</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="text-muted"><?= $this->e($r['faculty_name']) ?></td>
            <td><a href="<?= $this->url('/employees?department_id=' . (int) $r['id']) ?>"><?= $this->e($r['department_name']) ?></a></td>
            <td class="text-end"><?= (int) $r['total'] ?></td>
            <td class="text-end"><?= (int) $r['academic'] ?></td>
            <td class="text-end"><?= (int) $r['with_degree'] ?></td>
            <td class="text-end"><?= (int) $r['on_leave'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="fw-semibold">
        <tr>
          <th scope="row" colspan="2">Итого</th>
          <td class="text-end"><?= $totals['total'] ?></td>
          <td class="text-end"><?= $totals['academic'] ?></td>
          <td class="text-end"><?= $totals['with_degree'] ?></td>
          <td class="text-end"><?= $totals['on_leave'] ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
