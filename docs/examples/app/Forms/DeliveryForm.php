<?php

declare(strict_types=1);

namespace App\Forms;

use Ephraitech\Forms\Form;

class DeliveryForm extends Form
{
    protected function define(): void
    {
        $this->hidden('order_id');

        $this->fieldset('Contact', function (): void {
            $this->row(function (): void {
                $this->text('first_name', 'First name')->rules('required')->width(6);
                $this->text('last_name', 'Last name')->rules('required')->width(6);
            });

            $this->tel('phone', 'Phone number')->rules('required');
        });

        $this->fieldset('Delivery address', function (): void {
            $this->text('street', 'Street and building')->rules('required');

            $this->row(function (): void {
                $this->text('town', 'Town')->rules('required')->width(8);
                $this->text('postcode', 'Postcode');
            });
        })->description('We deliver within Kampala and Entebbe.');

        $this->row(function (): void {
            $this->fieldset('Billing', function (): void {
                $this->select('billing', 'Invoice to', ['me' => 'Me', 'company' => 'My company']);
            })->width(6);

            $this->fieldset('Timing', function (): void {
                $this->date('deliver_on', 'Deliver on')->rules('required|valid_date[Y-m-d]');
            })->width(6);
        })->addClass('g-4');

        $this->submit('Save delivery details');
    }
}
