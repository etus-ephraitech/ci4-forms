<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FormSubmissionModel extends Model
{
    protected $table         = 'form_submissions';
    protected $returnType    = 'array';
    protected $allowedFields = ['form_definition_id', 'payload'];
    protected $useTimestamps = true;
    protected array $casts   = ['payload' => 'json-array'];
}
