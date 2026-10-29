<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Forms\AppointmentForm;
use App\Models\AppointmentModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Appointments extends BaseController
{
    // --8<-- [start:index]
    public function index(): string
    {
        return view('appointments/index', [
            'appointments' => model(AppointmentModel::class)
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->findAll(),
        ]);
    }
    // --8<-- [end:index]

    // --8<-- [start:create]
    public function new(): string
    {
        return $this->showForm($this->createForm(), 'Book an appointment');
    }

    public function create(): RedirectResponse|string
    {
        $form = $this->createForm();

        if (! $form->validate() || ! $this->isBookable($form)) {
            return $this->showForm($form, 'Book an appointment');
        }

        model(AppointmentModel::class)->insert($form->validated());

        return redirect()->to('/appointments')->with('message', 'Appointment booked.');
    }
    // --8<-- [end:create]

    // --8<-- [start:edit]
    public function edit(int $id): string
    {
        $form = $this->editForm($id)->fill($this->findOrFail($id));

        return $this->showForm($form, 'Change appointment');
    }

    public function update(int $id): RedirectResponse|string
    {
        $this->findOrFail($id);

        $form = $this->editForm($id);

        if (! $form->validate() || ! $this->isBookable($form)) {
            return $this->showForm($form, 'Change appointment');
        }

        model(AppointmentModel::class)->update($id, $form->validated());

        // 303 tells any client to load the list with GET. Without it,
        // CodeIgniter answers a genuine PATCH with 307, which repeats the PATCH.
        return redirect()->to('/appointments', 303)->with('message', 'Appointment changed.');
    }
    // --8<-- [end:edit]

    // --8<-- [start:forms]
    private function createForm(): AppointmentForm
    {
        return (new AppointmentForm())->action('appointments');
    }

    private function editForm(int $id): AppointmentForm
    {
        $form = (new AppointmentForm())
            ->method('patch')
            ->action('appointments/' . $id);

        $form->getField('submit')->label('Save changes');

        return $form;
    }
    // --8<-- [end:forms]

    // --8<-- [start:rule]
    private function isBookable(AppointmentForm $form): bool
    {
        if ($form->validated()['appointment_date'] < date('Y-m-d')) {
            $form->setError('appointment_date', 'Choose today or a later date.');
        }

        return $form->isValid();
    }
    // --8<-- [end:rule]

    // --8<-- [start:helpers]
    private function findOrFail(int $id): array
    {
        $appointment = model(AppointmentModel::class)->find($id);

        if ($appointment === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $appointment;
    }

    private function showForm(AppointmentForm $form, string $title): string
    {
        return view('appointments/form', ['form' => $form, 'title' => $title]);
    }
    // --8<-- [end:helpers]
}
