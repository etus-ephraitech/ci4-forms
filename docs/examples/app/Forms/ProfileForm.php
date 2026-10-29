<?php

declare(strict_types=1);

namespace App\Forms;

use Ephraitech\Forms\Form;

class ProfileForm extends Form
{
    protected function define(): void
    {
        $this->text('full_name', 'Full name')->rules('required|max_length[100]');

        $this->file('photo', 'Profile photo')
            ->required()
            ->maxSize(2048)
            ->image()
            ->mimes(['image/jpeg', 'image/png', 'image/webp'])
            ->help('JPG, PNG or WebP, up to 2 MB.');

        $this->file('certificates', 'Certificates')
            ->multiple()
            ->maxSize(4096)
            ->extensions(['pdf'])
            ->mimes(['application/pdf'])
            ->help('Optional. PDF files, up to 4 MB each.');

        $this->submit('Save profile');
    }
}
