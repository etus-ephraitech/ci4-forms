<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\FormPolicy;
use App\Models\FormDefinitionModel;
use App\Models\FormSubmissionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Schema\SchemaException;

/**
 * Public pages for forms written by admins.
 */
class Forms extends BaseController
{
    public function show(int $id): string
    {
        $definition = $this->findOrFail($id);
        $form       = $this->load($definition);

        return $form === null ? $this->unavailable() : view('forms/show', ['title' => $definition['title'], 'form' => $form]);
    }

    // --8<-- [start:submit]
    public function submit(int $id): RedirectResponse|string
    {
        $definition = $this->findOrFail($id);
        $form       = $this->load($definition);

        if ($form === null) {
            return $this->unavailable();
        }

        if (! $form->validate()) {
            return view('forms/show', ['title' => $definition['title'], 'form' => $form]);
        }

        // The admin's fields are stored as one JSON payload, never as columns.
        model(FormSubmissionModel::class)->insert([
            'form_definition_id' => $id,
            'payload'            => $form->validated(),
        ]);

        return redirect()->to("forms/{$id}", 303)->with('message', 'Thank you, we received your answers.');
    }
    // --8<-- [end:submit]

    // --8<-- [start:load]
    private function load(array $definition): ?Form
    {
        try {
            $form = Form::fromSchema($definition['schema'], policy: FormPolicy::forAdmins());
        } catch (SchemaException $e) {
            // Saved before the policy changed, or edited in the database directly.
            log_message('error', 'Form {id} cannot be shown: {errors}', ['id' => $definition['id'], 'errors' => implode('; ', $e->getMessages())]);

            return null;
        }

        // The application decides where forms are sent; schemas cannot.
        return $form->action('forms/' . $definition['id']);
    }
    // --8<-- [end:load]

    private function unavailable(): string
    {
        $this->response->setStatusCode(503);

        return view('forms/unavailable');
    }

    private function findOrFail(int $id): array
    {
        return model(FormDefinitionModel::class)->find($id) ?? throw PageNotFoundException::forPageNotFound();
    }
}
