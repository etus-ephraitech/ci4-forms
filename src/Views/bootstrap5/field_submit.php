<?php
/**
 * Submit button.
 *
 * @var string                  $name
 * @var string                  $id
 * @var string                  $label
 * @var string                  $attributes
 * @var bool                    $bare
 * @var Closure(mixed): string  $e
 * @var Closure(string): string $class
 */
?>
<?php if (! $bare): ?>
<div<?= $class('group') ?>>
<?php endif ?>
    <button type="submit" name="<?= $e($name) ?>" id="<?= $e($id) ?>" value="1"<?= $class('control') ?><?= $attributes ?>><?= $e($label) ?></button>
<?php if (! $bare): ?>
</div>
<?php endif ?>
