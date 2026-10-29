<?php
/**
 * The compact theme's text-like inputs: label and input on one line.
 * Variables are the same as in the package's field_input view.
 */
?>
<?php if (! $bare): ?>
<div<?= $class('group') ?>>
    <div class="row align-items-center g-2">
        <label for="<?= $e($id) ?>" class="col-sm-4 col-form-label col-form-label-sm"><?= $e($label) ?><?= $requiredMarker ?></label>
        <div class="col-sm-8">
<?php endif ?>
            <input type="<?= $e($inputType) ?>" name="<?= $e($inputName) ?>" id="<?= $e($id) ?>" value="<?= $e($value) ?>"<?= $class('control') ?><?= $attributes ?>>
<?php if (! $bare): ?>
<?php if ($error !== null): ?>
            <div id="<?= $e($errorId) ?>"<?= $class('error') ?>><?= $e($error) ?></div>
<?php endif ?>
        </div>
    </div>
</div>
<?php endif ?>
