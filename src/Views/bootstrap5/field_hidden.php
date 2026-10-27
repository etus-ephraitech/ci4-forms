<?php
/**
 * Hidden input. No label, wrapper or error output.
 *
 * @var string                 $name
 * @var string                 $value
 * @var string                 $attributes
 * @var Closure(mixed): string $e
 */
?>
<input type="hidden" name="<?= $e($name) ?>" value="<?= $e($value) ?>"<?= $attributes ?>>
