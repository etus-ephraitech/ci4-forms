<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\FormPolicy;
use App\Models\FormDefinitionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Ephraitech\Forms\Schema\FormSchema;

class FormEditor extends BaseController
{
    public function edit(int $id): string
    {
        $definition = $this->findOrFail($id);

        return view('admin/form_editor', [
            'definition' => $definition,
            'json'       => json_encode($definition['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'errors'     => [],
        ]);
    }

    // --8<-- [start:save]
    public function save(int $id): RedirectResponse|string
    {
        $definition = $this->findOrFail($id);
        $json       = (string) $this->request->getPost('schema');
        $check      = FormSchema::check($json, FormPolicy::forAdmins());

        if (! $check->isValid()) {
            $this->response->setStatusCode(422);

            return view('admin/form_editor', [
                'definition' => $definition,
                'json'       => $json,
                'errors'     => $check->getMessages(),
            ]);
        }

        model(FormDefinitionModel::class)->update($id, ['schema' => $check->getSchema()]);

        return redirect()->to("admin/forms/{$id}/edit")->with('message', 'Form saved.');
    }
    // --8<-- [end:save]

    // --8<-- [start:check]
    /**
     * For a "Check" button in the editor: JSON in, JSON out.
     */
    public function check(): ResponseInterface
    {
        $check = FormSchema::check((string) $this->request->getPost('schema'), FormPolicy::forAdmins());

        return $this->response->setJSON($check->toArray() + [
            'messages' => $check->getMessages(),
            'csrf'     => csrf_hash(),   // CodeIgniter renews the token after each request
        ]);
    }
    // --8<-- [end:check]

    private function findOrFail(int $id): array
    {
        return model(FormDefinitionModel::class)->find($id) ?? throw PageNotFoundException::forPageNotFound();
    }
}
