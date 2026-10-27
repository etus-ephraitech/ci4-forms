<?php
/**
 * Multi-line text area. See Ephraitech\Forms\Renderer for the variables.
 *
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
    <textarea name="<?= $e($name) ?>" id="<?= $e($id) ?>"<?= $class('control') ?><?= $attributes ?>><?= $e($value) ?></textarea>
<?php if (! $bare): ?>
<?php if ($error !== null): ?>
    <div id="<?= $e($errorId) ?>"<?= $class('error') ?>><?= $e($error) ?></div>
<?php endif ?>
<?php if ($help !== null): ?>
    <div id="<?= $e($helpId) ?>"<?= $class('help') ?>><?= $e($help) ?></div>
<?php endif ?>
</div>
<?php endif ?>
