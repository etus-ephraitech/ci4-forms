<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Contracts;

/**
 * A field that receives uploaded files.
 *
 * The form treats these fields differently from value fields: uploads are
 * read from the current request (as CodeIgniter's file rules do), they are
 * never part of validated() data, and they make the form multipart.
 * Retrieve them with Form::getFile() and Form::getFiles().
 */
interface FileFieldInterface extends FieldInterface
{
}
