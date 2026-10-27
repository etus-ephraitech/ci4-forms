<?php
/**
 * Single checkbox with its label beside it.
 * See Ephraitech\Forms\Renderer for the variables.
 *
 * @var string                  $name
 * @var string                  $id
 * @var string                  $label
 * @var string                  $checkedValue
 * @var bool                    $checked
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
<?php endif ?>
    <div<?= $class('wrapper') ?>>
        <input type="checkbox" name="<?= $e($name) ?>" id="<?= $e($id) ?>" value="<?= $e($checkedValue) ?>"<?= $checked ? ' checked' : '' ?><?= $class('control') ?><?= $attributes ?>>
        <label for="<?= $e($id) ?>"<?= $class('check_label') ?>><?= $e($label) ?><?= $requiredMarker ?></label>
<?php if (! $bare && $error !== null): ?>
        <div id="<?= $e($errorId) ?>"<?= $class('error') ?>><?= $e($error) ?></div>
<?php endif ?>
    </div>
<?php if (! $bare): ?>
<?php if ($help !== null): ?>
    <div id="<?= $e($helpId) ?>"<?= $class('help') ?>><?= $e($help) ?></div>
<?php endif ?>
</div>
<?php endif ?>
