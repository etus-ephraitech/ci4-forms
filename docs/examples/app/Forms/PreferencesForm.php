<?php

declare(strict_types=1);

namespace App\Forms;

use Ephraitech\Forms\Form;

class PreferencesForm extends Form
{
    protected function define(): void
    {
        $this->checkboxes('interests', 'What are you interested in?', [
            'suits'       => 'Suits',
            'shirts'      => 'Shirts',
            'kanzu'       => 'Kanzu',
            'accessories' => 'Accessories',
        ])
            ->rules('required')
            ->messages(['required' => 'Choose at least one.'])
            ->inline();

        $this->select('branches', 'Branches you visit', [
            'kampala' => 'Kampala',
            'entebbe' => 'Entebbe',
            'jinja'   => 'Jinja',
        ])
            ->multiple()
            ->size(3)
            ->help('Hold Ctrl (Cmd on a Mac) to choose several.');

        $this->submit('Save preferences');
    }
}
