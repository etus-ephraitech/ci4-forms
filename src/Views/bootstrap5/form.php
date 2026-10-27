<?php
/**
 * Whole-form layout.
 *
 * @var \Ephraitech\Forms\Form $form
 * @var bool                   $showSummary
 */
?>
<?= $form->open() ?>
<?php if ($showSummary): ?>
<?= $form->errorSummary() ?>
<?php endif ?>
<?= $form->fields() ?>
<?= $form->close() ?>
