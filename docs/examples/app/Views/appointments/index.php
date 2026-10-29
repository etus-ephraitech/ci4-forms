<?php use App\Forms\AppointmentForm; ?>
<h1>Appointments</h1>

<?php if (session('message')): ?>
    <div class="alert alert-success"><?= esc(session('message')) ?></div>
<?php endif ?>

<p><a href="<?= site_url('appointments/new') ?>" class="btn btn-primary">Book an appointment</a></p>

<?php if ($appointments === []): ?>
    <p>No appointments yet.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr><th>Date</th><th>Time</th><th>Name</th><th>Service</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($appointments as $appointment): ?>
            <tr>
                <td><?= esc($appointment['appointment_date']) ?></td>
                <td><?= esc($appointment['appointment_time']) ?></td>
                <td><?= esc($appointment['full_name']) ?></td>
                <td><?= esc(AppointmentForm::SERVICES[$appointment['service']] ?? $appointment['service']) ?></td>
                <td><a href="<?= site_url('appointments/' . $appointment['id'] . '/edit') ?>">Change</a></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
