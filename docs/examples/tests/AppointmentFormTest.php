<?php

declare(strict_types=1);

use App\Forms\AppointmentForm;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AppointmentFormTest extends CIUnitTestCase
{
    /**
     * @return array<string, string>
     */
    private function valid(array $overrides = []): array
    {
        return array_merge([
            'full_name'        => 'Jane Namuli',
            'phone'            => '0772123456',
            'service'          => 'fitting',
            'appointment_date' => '2030-01-15',
            'appointment_time' => '10:30',
        ], $overrides);
    }

    public function testValidDataPasses(): void
    {
        $form = new AppointmentForm();

        $this->assertTrue($form->validate($this->valid()));
    }

    public function testRequiredFields(): void
    {
        $form = new AppointmentForm();

        $this->assertFalse($form->validate([]));
        $this->assertTrue($form->hasError('full_name'));
        $this->assertTrue($form->hasError('phone'));
        $this->assertFalse($form->hasError('email'), 'email is optional');
    }

    public function testUnknownServiceIsRejected(): void
    {
        $form = new AppointmentForm();

        $this->assertFalse($form->validate($this->valid(['service' => 'free_suit'])));
        $this->assertSame('Please choose a valid option for Service.', $form->getError('service'));
    }

    public function testValidatedDataIsClean(): void
    {
        $form = new AppointmentForm();
        $form->validate($this->valid(['full_name' => '  Jane Namuli ', 'is_admin' => '1']));

        $data = $form->validated();

        $this->assertSame('Jane Namuli', $data['full_name']);
        $this->assertNull($data['email']);
        $this->assertArrayNotHasKey('is_admin', $data);
    }
}
