<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class CustomerPreferenceModel extends Model
{
    protected $table         = 'customer_preferences';
    protected $returnType    = 'array';
    protected $allowedFields = ['customer_id', 'interests', 'branches'];

    // Lists are stored as JSON text and come back as arrays.
    protected array $casts = [
        'interests' => 'json-array',
        'branches'  => 'json-array',
    ];
}
