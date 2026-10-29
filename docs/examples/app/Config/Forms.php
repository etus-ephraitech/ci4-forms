<?php

declare(strict_types=1);

namespace Config;

use App\Forms\Fields\MoneyField;
use App\Models\ServiceModel;
use Ephraitech\Forms\Config\Forms as BaseForms;

class Forms extends BaseForms
{
    // --8<-- [start:field-types]
    public array $fieldTypes = [
        'money' => MoneyField::class,
    ];
    // --8<-- [end:field-types]

    // --8<-- [start:option-providers]
    public array $optionProviders = [
        'services' => [ServiceModel::class, 'dropdown'],
    ];
    // --8<-- [end:option-providers]

    // --8<-- [start:settings]
    public bool $showErrorSummary = true;
    // --8<-- [end:settings]

    // --8<-- [start:constructor]
    public function __construct()
    {
        parent::__construct();

        // Change single classes of the Bootstrap 5 theme.
        $this->themes['bootstrap5']['classes']['submit'] = 'btn btn-success';
        $this->themes['bootstrap5']['classes']['group']  = 'mb-4';

        // A second theme: your own views in app/Views/forms/compact. It
        // extends Bootstrap 5, so templates it lacks come from there.
        $this->themes['compact'] = [
            'views'           => 'forms/compact',
            'extends'         => 'bootstrap5',
            'required_marker' => ' <abbr title="required">*</abbr>',
            'classes'         => array_merge($this->themes['bootstrap5']['classes'], [
                'group' => 'mb-2',
                'input' => 'form-control form-control-sm',
            ]),
        ];
    }
    // --8<-- [end:constructor]
}
