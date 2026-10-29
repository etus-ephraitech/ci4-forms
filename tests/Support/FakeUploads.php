<?php

declare(strict_types=1);

/**
 * Test-only stand-ins for PHP's is_uploaded_file() and move_uploaded_file().
 *
 * UploadedFile::isValid() calls is_uploaded_file() without a leading
 * backslash, so PHP looks for CodeIgniter\HTTP\Files\is_uploaded_file()
 * first. Outside a real HTTP upload the global function always returns
 * false; this version accepts any existing file so tests can build uploads
 * from temporary files. CodeIgniter's own test suite uses the same approach.
 *
 * Loaded only by the test suite; never shipped in the package.
 */

namespace CodeIgniter\HTTP\Files;

if (! function_exists(__NAMESPACE__ . '\is_uploaded_file')) {
    function is_uploaded_file(string $filename): bool
    {
        return is_file($filename);
    }
}

if (! function_exists(__NAMESPACE__ . '\move_uploaded_file')) {
    function move_uploaded_file(string $from, string $to): bool
    {
        return copy($from, $to);
    }
}
