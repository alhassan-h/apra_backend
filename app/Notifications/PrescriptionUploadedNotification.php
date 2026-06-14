<?php

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrescriptionUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Prescription $prescription) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Prescription Upload Received')
            ->line("Prescription {$this->prescription->prescription_number} has been received and is being processed.")
            ->line('You will be notified once the AI extraction is complete.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'                => 'PRESCRIPTION_UPLOADED',
            'prescription_id'     => $this->prescription->id,
            'prescription_number' => $this->prescription->prescription_number,
            'message'             => "Prescription {$this->prescription->prescription_number} uploaded successfully.",
        ];
    }
}
