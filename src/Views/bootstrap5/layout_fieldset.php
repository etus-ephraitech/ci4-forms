<?php
/**
 * A titled section of the form.
 *
 * @var string                  $legend
 * @var string|null             $description
 * @var string|null             $id
 * @var string                  $content     Rendered fields and groups inside the section
 * @var Closure(mixed): string  $e
 * @var Closure(string): string $class       $class('section') includes the fieldset's own classes
 */
?>
<fieldset<?= $id !== null ? ' id="' . $e($id) . '"' : '' ?><?= $class('section') ?>>
<?php if ($legend !== ''): ?>
    <legend<?= $class('section_legend') ?>><?= $e($legend) ?></legend>
<?php endif ?>
<?php if ($description !== null): ?>
    <p<?= $class('section_description') ?>><?= $e($description) ?></p>
<?php endif ?>
<?= $content ?>
</fieldset>
