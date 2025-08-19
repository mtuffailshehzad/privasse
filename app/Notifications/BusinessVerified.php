<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BusinessVerified extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Business Verification Approved - Privasee')
            ->greeting('Congratulations!')
            ->line('Your business registration has been approved and verified.')
            ->line('You can now start adding venues and creating offers on the Privasee platform.')
            ->action('Access Business Dashboard', url('/business/dashboard'))
            ->line('Welcome to the Privasee business community!');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Business Verified',
            'message' => 'Your business has been successfully verified and approved.',
            'type' => 'business_verified',
            'action_url' => url('/business/dashboard'),
        ];
    }

    public function toSms($notifiable): string
    {
        return "Congratulations! Your business {$notifiable->name} has been verified on Privasee. You can now start adding venues and offers.";
    }
}