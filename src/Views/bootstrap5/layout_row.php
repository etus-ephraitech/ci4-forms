<?php
/**
 * A row of fields side by side.
 *
 * @var list<array{class: string|null, html: string}> $columns Rendered children; class is null for hidden fields
 * @var Closure(mixed): string                        $e
 * @var Closure(string): string                       $class   $class('row') includes the row's own classes
 */
?>
<div<?= $class('row') ?>>
<?php foreach ($columns as $column): ?>
<?php if ($column['class'] === null): ?>
<?= $column['html'] ?>
<?php else: ?>
    <div<?= $column['class'] === '' ? '' : ' class="' . $e($column['class']) . '"' ?>>
<?= $column['html'] ?>
    </div>
<?php endif ?>
<?php endforeach ?>
</div>
