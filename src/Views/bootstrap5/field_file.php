<?php
/**
 * File upload input. Browsers never refill file inputs, so there is no
 * value; after a failed submission $reselect holds a hint asking the user to
 * choose the file again. See Ephraitech\Forms\Renderer for the variables.
 *
 * @var string                  $inputName
 * @var string                  $id
 * @var string                  $label
 * @var string                  $requiredMarker
 * @var string|null             $error
 * @var string                  $errorId
 * @var string|null             $help
 * @var string                  $helpId
 * @var string|null             $reselect
 * @var string                  $reselectId
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
    <input type="file" name="<?= $e($inputName) ?>" id="<?= $e($id) ?>"<?= $class('control') ?><?= $attributes ?>>
<?php if (! $bare): ?>
<?php if ($error !== null): ?>
    <div id="<?= $e($errorId) ?>"<?= $class('error') ?>><?= $e($error) ?></div>
<?php endif ?>
<?php if ($reselect !== null): ?>
    <div id="<?= $e($reselectId) ?>"<?= $class('reselect') ?>><?= $e($reselect) ?></div>
<?php endif ?>
<?php if ($help !== null): ?>
    <div id="<?= $e($helpId) ?>"<?= $class('help') ?>><?= $e($help) ?></div>
<?php endif ?>
</div>
<?php endif ?>
