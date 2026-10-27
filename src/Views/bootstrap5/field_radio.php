<?php
/**
 * Radio group inside a fieldset, with the field label as its legend.
 * See Ephraitech\Forms\Renderer for the variables.
 *
 * @var string                  $name
 * @var string                  $label
 * @var string                  $value
 * @var array<string, string>   $options
 * @var array<string, string>   $optionIds
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
<fieldset<?= $class('group') ?>>
    <legend<?= $class('legend') ?>><?= $e($label) ?><?= $requiredMarker ?></legend>
<?php endif ?>
<?php foreach ($options as $optionValue => $optionLabel): ?>
    <div<?= $class('wrapper') ?>>
        <input type="radio" name="<?= $e($name) ?>" id="<?= $e($optionIds[(string) $optionValue]) ?>" value="<?= $e($optionValue) ?>"<?= (string) $optionValue === $value ? ' checked' : '' ?><?= $class('control') ?><?= $attributes ?>>
        <label for="<?= $e($optionIds[(string) $optionValue]) ?>"<?= $class('check_label') ?>><?= $e($optionLabel) ?></label>
    </div>
<?php endforeach ?>
<?php if (! $bare): ?>
<?php if ($error !== null): ?>
    <div id="<?= $e($errorId) ?>"<?= $class('error') ?>><?= $e($error) ?></div>
<?php endif ?>
<?php if ($help !== null): ?>
    <div id="<?= $e($helpId) ?>"<?= $class('help') ?>><?= $e($help) ?></div>
<?php endif ?>
</fieldset>
<?php endif ?>
