<?php

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExtractionCompleteNotification extends Notification
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
            ->subject('AI Extraction Complete — Review Required')
            ->line("AI extraction for prescription {$this->prescription->prescription_number} is complete.")
            ->line('Please review the extracted data and approve or correct it.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'                => 'EXTRACTION_COMPLETE',
            'prescription_id'     => $this->prescription->id,
            'prescription_number' => $this->prescription->prescription_number,
            'message'             => "Extraction complete for {$this->prescription->prescription_number}. Review required.",
        ];
    }
}
