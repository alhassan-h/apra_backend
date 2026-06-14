<?php

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrescriptionRejectedNotification extends Notification
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
            ->subject('Prescription Rejected')
            ->line("Prescription {$this->prescription->prescription_number} has been rejected.")
            ->line('Please check the review notes for details.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'                => 'PRESCRIPTION_REJECTED',
            'prescription_id'     => $this->prescription->id,
            'prescription_number' => $this->prescription->prescription_number,
            'message'             => "Prescription {$this->prescription->prescription_number} was rejected.",
        ];
    }
}
