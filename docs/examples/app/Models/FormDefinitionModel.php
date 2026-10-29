<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FormDefinitionModel extends Model
{
    protected $table         = 'form_definitions';
    protected $returnType    = 'array';
    protected $allowedFields = ['title', 'schema'];
    protected $useTimestamps = true;
    protected array $casts   = ['schema' => 'json-array'];
}
