<?php
/**
 * @var \Ephraitech\Forms\Tests\Support\Fields\MoneyField $field
 * @var Closure(mixed): string $e
 * @var Closure(string): string $class
 */
?>
<div<?= $class('group') ?>>
    <label for="<?= $e($id) ?>"<?= $class('label') ?>><?= $e($label) ?><?= $requiredMarker ?></label>
    <div class="input-group">
        <span class="input-group-text"><?= $e($field->getCurrency()) ?></span>
        <input type="text" name="<?= $e($inputName) ?>" id="<?= $e($id) ?>" value="<?= $e($value) ?>"<?= $class('control') ?><?= $attributes ?>>
    </div>
<?php if ($error !== null): ?>
    <div id="<?= $e($errorId) ?>"<?= $class('error_block') ?>><?= $e($error) ?></div>
<?php endif ?>
</div>
