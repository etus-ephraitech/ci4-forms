<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Forms\AppointmentForm;
use App\Models\AppointmentModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class Appointments extends BaseController
{
    use ResponseTrait;

    // --8<-- [start:create]
    public function create(): ResponseInterface
    {
        $form = new AppointmentForm();

        // Reads JSON bodies as well as form posts.
        if (! $form->validate()) {
            return $this->failValidationErrors($form->getErrors());
        }

        $id = model(AppointmentModel::class)->insert($form->validated());

        return $this->respondCreated(['id' => $id] + $form->validated());
    }
    // --8<-- [end:create]

    // --8<-- [start:update]
    public function update(int $id): ResponseInterface
    {
        if (model(AppointmentModel::class)->find($id) === null) {
            return $this->failNotFound('No such appointment.');
        }

        // A genuine PATCH: the form reads its JSON or URL-encoded body.
        $form = (new AppointmentForm())->method('patch');

        if (! $form->validate()) {
            return $this->failValidationErrors($form->getErrors());
        }

        model(AppointmentModel::class)->update($id, $form->validated());

        return $this->respond(['id' => $id] + $form->validated());
    }
    // --8<-- [end:update]
}
