<?php

declare(strict_types=1);

namespace App\Libraries;

use Ephraitech\Forms\Schema\SchemaPolicy;

/**
 * What admins may put in the forms they write. Every place that checks or
 * loads an admin's schema uses this one policy, so the editor, the public
 * page and the submissions screen always agree.
 */
final class FormPolicy
{
    public static function forAdmins(): SchemaPolicy
    {
        return SchemaPolicy::restricted()
            // Names the app uses itself.
            ->forbidNames('form_definition_id', 'payload');
    }
}
