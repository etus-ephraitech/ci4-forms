<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ContactMessageModel extends Model
{
    protected $table         = 'contact_messages';
    protected $allowedFields = ['full_name', 'email', 'topic', 'message'];
    protected $useTimestamps = true;
}
