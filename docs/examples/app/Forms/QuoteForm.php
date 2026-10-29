<?php

declare(strict_types=1);

namespace App\Forms;

use Ephraitech\Forms\Form;

class QuoteForm extends Form
{
    protected function define(): void
    {
        $this->text('item', 'Item')->rules('required');

        // money() exists because MoneyField is registered in app/Config/Forms.php.
        $this->money('budget', 'Budget')
            ->rules('required|is_natural_no_zero')
            ->placeholder('1,500,000');

        $this->money('deposit', 'Deposit')
            ->currency('USD')
            ->rules('permit_empty|is_natural');

        $this->submit('Request a quote');
    }
}
