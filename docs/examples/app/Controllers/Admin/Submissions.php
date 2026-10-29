<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\FormPolicy;
use App\Models\FormDefinitionModel;
use App\Models\FormSubmissionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Ephraitech\Forms\Form;

class Submissions extends BaseController
{
    public function index(int $id): string
    {
        $definition = model(FormDefinitionModel::class)->find($id) ?? throw PageNotFoundException::forPageNotFound();

        return view('admin/submissions', [
            'definition'  => $definition,
            'form'        => Form::fromSchema($definition['schema'], policy: FormPolicy::forAdmins()),
            'submissions' => model(FormSubmissionModel::class)
                ->where('form_definition_id', $id)
                ->orderBy('id', 'DESC')
                ->findAll(50),
        ]);
    }
}
