<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table         = 'services';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'active'];

    /**
     * Active services as id => name, for option providers.
     *
     * @return array<int|string, string>
     */
    public function dropdown(): array
    {
        $rows = $this->select('id, name')->where('active', 1)->orderBy('name')->findAll();

        return array_column($rows, 'name', 'id');
    }
}
