<h1>Contact us</h1>

<?php if (session('message')): ?>
    <div class="alert alert-success"><?= esc(session('message')) ?></div>
<?php endif ?>

<?= $form ?>
