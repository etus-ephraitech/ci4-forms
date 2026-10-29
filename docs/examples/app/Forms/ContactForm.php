<?php

declare(strict_types=1);

namespace App\Forms;

use Ephraitech\Forms\Form;

class ContactForm extends Form
{
    protected function define(): void
    {
        $this->text('full_name', 'Full name')
            ->rules('required|max_length[100]')
            ->autocomplete('name');

        $this->email('email', 'Email address')
            ->rules('required|valid_email|max_length[150]')
            ->autocomplete('email');

        $this->select('topic', 'Topic', [
            'booking' => 'A booking',
            'pricing' => 'Prices',
            'other'   => 'Something else',
        ])
            ->placeholder('Choose a topic')
            ->rules('required');

        $this->textarea('message', 'Message')
            ->rows(5)
            ->rules('required|max_length[2000]');

        $this->submit('Send message');
    }
}
