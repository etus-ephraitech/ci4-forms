<?php
/**
 * Dropdown list, single or multiple, with optional placeholder and option
 * groups. See Ephraitech\Forms\Renderer for the variables.
 *
 * @var string                                         $inputName
 * @var string                                         $id
 * @var string                                         $label
 * @var string                                         $value
 * @var list<string>                                   $values
 * @var bool                                           $multiple
 * @var string|null                                    $placeholder
 * @var array<string, array<string, string>|string>    $options
 * @var string                                         $requiredMarker
 * @var string|null                                    $error
 * @var string                                         $errorId
 * @var string|null                                    $help
 * @var string                                         $helpId
 * @var string                                         $attributes
 * @var bool                                           $bare
 * @var Closure(mixed): string                         $e
 * @var Closure(string): string                        $class
 */
?>
<?php if (! $bare): ?>
<div<?= $class('group') ?>>
    <label for="<?= $e($id) ?>"<?= $class('label') ?>><?= $e($label) ?><?= $requiredMarker ?></label>
<?php endif ?>
    <select name="<?= $e($inputName) ?>" id="<?= $e($id) ?>"<?= $class('control') ?><?= $attributes ?>>
<?php if ($placeholder !== null): ?>
        <option value=""<?= $value === '' ? ' selected' : '' ?>><?= $e($placeholder) ?></option>
<?php endif ?>
<?php foreach ($options as $optionValue => $optionLabel): ?>
<?php if (is_array($optionLabel)): ?>
        <optgroup label="<?= $e($optionValue) ?>">
<?php foreach ($optionLabel as $groupValue => $groupLabel): ?>
            <option value="<?= $e($groupValue) ?>"<?= in_array((string) $groupValue, $values, true) ? ' selected' : '' ?>><?= $e($groupLabel) ?></option>
<?php endforeach ?>
        </optgroup>
<?php else: ?>
        <option value="<?= $e($optionValue) ?>"<?= in_array((string) $optionValue, $values, true) ? ' selected' : '' ?>><?= $e($optionLabel) ?></option>
<?php endif ?>
<?php endforeach ?>
    </select>
<?php if (! $bare): ?>
<?php if ($error !== null): ?>
    <div id="<?= $e($errorId) ?>"<?= $class('error') ?>><?= $e($error) ?></div>
<?php endif ?>
<?php if ($help !== null): ?>
    <div id="<?= $e($helpId) ?>"<?= $class('help') ?>><?= $e($help) ?></div>
<?php endif ?>
</div>
<?php endif ?>
