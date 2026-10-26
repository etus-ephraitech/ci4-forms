<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Support;

use Ephraitech\Forms\Form;

/**
 * A realistic class-based form used across the test suite.
 */
class BookingForm extends Form
{
    protected function define(): void
    {
        $this->action('/bookings');

        $this->text('full_name', 'Full Name')->rules('required|min_length[3]|max_length[100]');
        $this->email('email', 'Email Address')->rules('required|valid_email');
        $this->select('service_id', 'Service', ['1' => 'Suit fitting', '2' => 'Alterations'])
            ->rules('required|in_list[1,2]');
        $this->date('appointment_date', 'Preferred Date')->rules('required|valid_date[Y-m-d]');
        $this->textarea('notes')->rules('permit_empty|max_length[500]');
        $this->checkbox('is_returning', 'I have booked before');
        $this->password('pin', 'Booking PIN')->rules('permit_empty|exact_length[4]');
        $this->submit('Book appointment');
    }
}
