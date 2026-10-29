<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forms\ContactForm;
use App\Models\ContactMessageModel;
use CodeIgniter\HTTP\RedirectResponse;

class Contact extends BaseController
{
    public function index(): string
    {
        return view('contact', ['form' => new ContactForm()]);
    }

    public function send(): RedirectResponse|string
    {
        $form = new ContactForm();

        if (! $form->validate()) {
            return view('contact', ['form' => $form]);
        }

        model(ContactMessageModel::class)->insert($form->validated());

        return redirect()->to('/contact')->with('message', 'Thank you. We will reply within one working day.');
    }
}
