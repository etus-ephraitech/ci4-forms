<?php
/**
 * List of all errors, each linking to its field when possible.
 *
 * @var list<array{message: string, target: string|null}> $items
 * @var Closure(mixed): string                            $e
 * @var Closure(string): string                           $class
 */
?>
<div<?= $class('error_summary') ?> role="alert">
    <ul class="mb-0">
<?php foreach ($items as $item): ?>
<?php if ($item['target'] !== null): ?>
        <li><a href="#<?= $e($item['target']) ?>" class="alert-link"><?= $e($item['message']) ?></a></li>
<?php else: ?>
        <li><?= $e($item['message']) ?></li>
<?php endif ?>
<?php endforeach ?>
    </ul>
</div>
