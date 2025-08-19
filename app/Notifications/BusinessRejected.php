<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reason;

    public function __construct(string $reason)
    {
        $this->reason = $reason;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Business Registration Update - Privasee')
            ->greeting('Hello!')
            ->line('We have reviewed your business registration application.')
            ->line('Unfortunately, we cannot approve your application at this time.')
            ->line('Reason: ' . $this->reason)
            ->line('Please review the requirements and submit a new application with the necessary corrections.')
            ->action('Submit New Application', url('/business/register'))
            ->line('If you have any questions, please contact our support team.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Business Registration Rejected',
            'message' => 'Your business registration was not approved. Reason: ' . $this->reason,
            'type' => 'business_rejected',
            'action_url' => url('/business/register'),
            'reason' => $this->reason,
        ];
    }

    public function toSms($notifiable): string
    {
        return "Your business registration on Privasee was not approved. Please check your email for details and next steps.";
    }
}