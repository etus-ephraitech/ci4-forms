<h1>Edit form: <?= esc($definition['title']) ?></h1>

<?php if (session('message')): ?>
    <div class="alert alert-success"><?= esc(session('message')) ?></div>
<?php endif ?>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger">
        <p>The form was not saved:</p>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><code><?= esc($error) ?></code></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>

<form method="post" action="<?= site_url('admin/forms/' . $definition['id']) ?>">
    <?= csrf_field() ?>
    <textarea name="schema" rows="20" class="form-control font-monospace"><?= esc($json) ?></textarea>
    <button type="submit" class="btn btn-primary mt-3">Save</button>
</form>
