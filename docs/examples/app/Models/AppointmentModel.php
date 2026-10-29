<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AppointmentModel extends Model
{
    protected $table         = 'appointments';
    protected $returnType    = 'array';
    protected $allowedFields = ['full_name', 'phone', 'email', 'service', 'appointment_date', 'appointment_time', 'notes'];
    protected $useTimestamps = true;
}
