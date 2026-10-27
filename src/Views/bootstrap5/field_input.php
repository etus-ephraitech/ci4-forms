<?php
/**
 * Single <input> field: text, email, password, number, tel, url, date.
 * See Ephraitech\Forms\Renderer for the variables available.
 *
 * @var string                  $inputType
 * @var string                  $name
 * @var string                  $id
 * @var string                  $label
 * @var string                  $value
 * @var string                  $requiredMarker
 * @var string|null             $error
 * @var string                  $errorId
 * @var string|null             $help
 * @var string                  $helpId
 * @var string                  $attributes
 * @var bool                    $bare
 * @var Closure(mixed): string  $e
 * @var Closure(string): string $class
 */
?>
<?php if (! $bare): ?>
<div<?= $class('group') ?>>
    <label for="<?= $e($id) ?>"<?= $class('label') ?>><?= $e($label) ?><?= $requiredMarker ?></label>
<?php endif ?>
    <input type="<?= $e($inputType) ?>" name="<?= $e($name) ?>" id="<?= $e($id) ?>" value="<?= $e($value) ?>"<?= $class('control') ?><?= $attributes ?>>
<?php if (! $bare): ?>
<?php if ($error !== null): ?>
    <div id="<?= $e($errorId) ?>"<?= $class('error') ?>><?= $e($error) ?></div>
<?php endif ?>
<?php if ($help !== null): ?>
    <div id="<?= $e($helpId) ?>"<?= $class('help') ?>><?= $e($help) ?></div>
<?php endif ?>
</div>
<?php endif ?>
