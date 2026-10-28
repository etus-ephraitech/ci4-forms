<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Support\Providers;

use CodeIgniter\Model;

/**
 * A CodeIgniter model used as an option provider. dropdown() returns fixed
 * rows so the test needs no database table.
 */
class BranchModel extends Model
{
    protected $table = 'branches';

    /**
     * @return array<string, string>
     */
    public function dropdown(): array
    {
        return ['kampala' => 'Kampala', 'entebbe' => 'Entebbe'];
    }
}
