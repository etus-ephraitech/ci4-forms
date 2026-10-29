<?php

declare(strict_types=1);

namespace App\Forms;

use Ephraitech\Forms\Form;

class AppointmentForm extends Form
{
    public const SERVICES = [
        'fitting'      => 'Suit fitting',
        'alterations'  => 'Alterations',
        'consultation' => 'Style consultation',
    ];

    protected function define(): void
    {
        $this->row(function (): void {
            $this->text('full_name', 'Full name')
                ->rules('required|min_length[3]|max_length[100]')
                ->width(6);

            $this->tel('phone', 'Phone number')
                ->rules('required|max_length[20]')
                ->placeholder('0772 123 456')
                ->width(6);
        });

        $this->email('email', 'Email address')
            ->rules('permit_empty|valid_email|max_length[150]')
            ->help('Optional. We send a reminder here.');

        $this->row(function (): void {
            $this->select('service', 'Service', self::SERVICES)
                ->placeholder('Choose a service')
                ->rules('required')
                ->width(6);

            $this->date('appointment_date', 'Date')
                ->rules('required|valid_date[Y-m-d]')
                ->width(3);

            $this->time('appointment_time', 'Time')
                ->rules('required|valid_date[H:i]')
                ->min('09:00')
                ->max('17:00')
                ->width(3);
        });

        $this->textarea('notes', 'Notes')
            ->rows(3)
            ->rules('permit_empty|max_length[500]');

        $this->submit('Book appointment');
    }
}
