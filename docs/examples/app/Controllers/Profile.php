<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forms\ProfileForm;
use CodeIgniter\HTTP\RedirectResponse;

class Profile extends BaseController
{
    public function edit(): string
    {
        return view('profile', ['form' => new ProfileForm()]);
    }

    public function update(): RedirectResponse|string
    {
        $form = new ProfileForm();

        if (! $form->validate()) {
            return view('profile', ['form' => $form]);
        }

        // --8<-- [start:store]
        $data = $form->validated();

        // store() moves the file under writable/uploads and returns its path there.
        $data['photo_path'] = $form->getFile('photo')->store('photos');

        $data['certificate_paths'] = [];

        foreach ($form->getFiles('certificates') as $file) {
            $data['certificate_paths'][] = $file->store('certificates');
        }
        // --8<-- [end:store]

        session()->setFlashdata('profile', $data);

        return redirect()->to('/profile')->with('message', 'Profile saved.');
    }
}
