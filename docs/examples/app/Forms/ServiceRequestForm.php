<?php

declare(strict_types=1);

namespace App\Forms;

use Ephraitech\Forms\Form;

class ServiceRequestForm extends Form
{
    protected function define(): void
    {
        $this->text('full_name', 'Full name')->rules('required');

        // Options come from the "services" provider in app/Config/Forms.php.
        $this->select('service_id', 'Service')
            ->optionsFrom('services')
            ->placeholder('Choose a service')
            ->rules('required');

        $this->submit('Send request');
    }
}
