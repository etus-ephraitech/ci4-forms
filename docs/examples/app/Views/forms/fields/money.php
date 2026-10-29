<?php
/**
 * View for App\Forms\Fields\MoneyField. It receives the same variables as the
 * package's own field views: $field, $id, $inputName, $label, $value, $error,
 * $help, $attributes, $requiredMarker, $bare, and the $e() and $class()
 * helpers.
 *
 * @var \App\Forms\Fields\MoneyField $field
 */
?>
<?php if (! $bare): ?>
<div<?= $class('group') ?>>
    <label for="<?= $e($id) ?>"<?= $class('label') ?>><?= $e($label) ?><?= $requiredMarker ?></label>
<?php endif ?>
    <div class="input-group<?= $error !== null ? ' has-validation' : '' ?>">
        <span class="input-group-text"><?= $e($field->getCurrency()) ?></span>
        <input type="text" name="<?= $e($inputName) ?>" id="<?= $e($id) ?>" value="<?= $e($value) ?>"<?= $class('control') ?><?= $attributes ?>>
<?php if (! $bare && $error !== null): ?>
        <div id="<?= $e($errorId) ?>"<?= $class('error') ?>><?= $e($error) ?></div>
<?php endif ?>
    </div>
<?php if (! $bare && $help !== null): ?>
    <div id="<?= $e($helpId) ?>"<?= $class('help') ?>><?= $e($help) ?></div>
<?php endif ?>
<?php if (! $bare): ?>
</div>
<?php endif ?>
