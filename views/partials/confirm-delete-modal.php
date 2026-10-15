<?php
/**
 * Модальное подтверждение удаления сотрудника.
 * app.js (лекция 11) открывает его по клику на кнопку с data-delete-url:
 * подставляет адрес в action формы и имя сотрудника в #confirm-delete-name.
 */
?>
<div class="modal fade" id="confirm-delete-modal" tabindex="-1" aria-labelledby="confirm-delete-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h3" id="confirm-delete-title">Удалить сотрудника?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Сотрудник <strong id="confirm-delete-name"></strong> будет удалён. Действие нельзя отменить.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
        <form id="confirm-delete-form" method="post" action="">
          <?= \App\Core\Csrf::field() ?>
          <button type="submit" class="btn btn-danger">Удалить</button>
        </form>
      </div>
    </div>
  </div>
</div>
