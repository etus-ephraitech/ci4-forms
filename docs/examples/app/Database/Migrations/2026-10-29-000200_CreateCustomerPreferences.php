<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerPreferences extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'INT', 'unsigned' => true],
            'interests'   => ['type' => 'TEXT'],
            'branches'    => ['type' => 'TEXT'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('customer_preferences');
    }

    public function down(): void
    {
        $this->forge->dropTable('customer_preferences');
    }
}
