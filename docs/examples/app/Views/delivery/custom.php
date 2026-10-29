<?= $form->open() ?>
<?= $form->errorSummary() ?>

<div class="row g-3">
    <div class="col-md-4"><?= $form->field('first_name') ?></div>
    <div class="col-md-4"><?= $form->field('last_name') ?></div>
    <div class="col-md-4"><?= $form->field('phone') ?></div>
</div>

<?= $form->fields('street', 'town', 'postcode', 'billing', 'deliver_on') ?>

<div class="d-flex gap-2">
    <?= $form->control('submit') ?>
    <a href="<?= site_url('orders') ?>" class="btn btn-outline-secondary">Cancel</a>
</div>

<?= $form->field('order_id') ?>
<?= $form->close() ?>
