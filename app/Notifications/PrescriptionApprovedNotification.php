<?php

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrescriptionApprovedNotification extends Notification
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
            ->subject('Prescription Verified')
            ->line("Prescription {$this->prescription->prescription_number} has been verified and approved.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'                => 'PRESCRIPTION_APPROVED',
            'prescription_id'     => $this->prescription->id,
            'prescription_number' => $this->prescription->prescription_number,
            'message'             => "Prescription {$this->prescription->prescription_number} has been approved.",
        ];
    }
}
