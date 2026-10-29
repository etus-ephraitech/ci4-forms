<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppointments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'full_name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'phone'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'email'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'service'          => ['type' => 'VARCHAR', 'constraint' => 20],
            'appointment_date' => ['type' => 'DATE'],
            // Stored as "HH:MM" text, exactly what the browser's time picker sends.
            'appointment_time' => ['type' => 'VARCHAR', 'constraint' => 5],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('appointments');
    }

    public function down(): void
    {
        $this->forge->dropTable('appointments');
    }
}
