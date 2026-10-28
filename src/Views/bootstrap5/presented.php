<?php
/**
 * Stored data shown with labels, as a description list.
 *
 * @var list<array{name: string, label: string, value: list<string>|string, text: string, empty: bool}> $rows
 * @var string                  $emptyText
 * @var Closure(mixed): string  $e
 * @var Closure(string): string $class
 */
?>
<dl<?= $class('presented') ?>>
<?php foreach ($rows as $row): ?>
    <dt<?= $class('presented_label') ?>><?= $e($row['label']) ?></dt>
    <dd<?= $class('presented_value') ?>>
<?php if ($row['empty']): ?>
        <span<?= $class('presented_empty') ?>><?= $e($emptyText) ?></span>
<?php elseif (is_array($row['value'])): ?>
        <ul class="list-unstyled mb-0">
<?php foreach ($row['value'] as $item): ?>
            <li><?= $e($item) ?></li>
<?php endforeach ?>
        </ul>
<?php else: ?>
        <?= nl2br($e($row['value']), false) ?>
<?php endif ?>
    </dd>
<?php endforeach ?>
</dl>
