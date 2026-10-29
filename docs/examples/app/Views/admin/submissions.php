<?php helper('forms') ?>
<h1>Answers: <?= esc($definition['title']) ?></h1>

<?php if ($submissions === []): ?>
    <p>No answers yet.</p>
<?php endif ?>

<?php foreach ($submissions as $submission): ?>
    <div class="card mb-3">
        <div class="card-header"><?= esc($submission['created_at']) ?></div>
        <div class="card-body">
            <?= forms_present($form, $submission['payload'], false) ?>
        </div>
    </div>
<?php endforeach ?>
