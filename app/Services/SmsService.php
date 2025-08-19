<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $client;
    protected $fromNumber;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->fromNumber = config('services.twilio.from');
    }

    public function sendOtp(string $phoneNumber): bool
    {
        try {
            // Generate OTP
            $otp = $this->generateOtp();
            
            // Store OTP in cache for 5 minutes
            $cacheKey = "otp_{$phoneNumber}";
            Cache::put($cacheKey, $otp, 300); // 5 minutes

            // Format phone number for UAE
            $formattedNumber = $this->formatPhoneNumber($phoneNumber);

            // Send SMS
            $message = $this->client->messages->create($formattedNumber, [
                'from' => $this->fromNumber,
                'body' => "Your Privasee verification code is: {$otp}. Valid for 5 minutes."
            ]);

            Log::info('OTP sent successfully', [
                'phone' => $phoneNumber,
                'message_sid' => $message->sid
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function verifyOtp(string $phoneNumber, string $otp): bool
    {
        try {
            $cacheKey = "otp_{$phoneNumber}";
            $storedOtp = Cache::get($cacheKey);

            if (!$storedOtp) {
                Log::warning('OTP verification failed - OTP expired or not found', [
                    'phone' => $phoneNumber
                ]);
                return false;
            }

            if ($storedOtp !== $otp) {
                Log::warning('OTP verification failed - Invalid OTP', [
                    'phone' => $phoneNumber,
                    'provided_otp' => $otp
                ]);
                return false;
            }

            // Remove OTP from cache after successful verification
            Cache::forget($cacheKey);

            Log::info('OTP verified successfully', [
                'phone' => $phoneNumber
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('OTP verification error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function sendWelcomeSms(string $phoneNumber, string $firstName): bool
    {
        try {
            $formattedNumber = $this->formatPhoneNumber($phoneNumber);
            
            $message = $this->client->messages->create($formattedNumber, [
                'from' => $this->fromNumber,
                'body' => "Welcome to Privasee, {$firstName}! Discover premium beauty & wellness venues across UAE. Download our app to get started."
            ]);

            Log::info('Welcome SMS sent successfully', [
                'phone' => $phoneNumber,
                'message_sid' => $message->sid
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function sendOfferNotification(string $phoneNumber, string $offerTitle, string $venueName): bool
    {
        try {
            $formattedNumber = $this->formatPhoneNumber($phoneNumber);
            
            $message = $this->client->messages->create($formattedNumber, [
                'from' => $this->fromNumber,
                'body' => "New offer at {$venueName}: {$offerTitle}. Check the Privasee app for details!"
            ]);

            Log::info('Offer notification SMS sent successfully', [
                'phone' => $phoneNumber,
                'message_sid' => $message->sid
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send offer notification SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function sendSubscriptionReminder(string $phoneNumber, string $firstName, int $daysLeft): bool
    {
        try {
            $formattedNumber = $this->formatPhoneNumber($phoneNumber);
            
            $message = $this->client->messages->create($formattedNumber, [
                'from' => $this->fromNumber,
                'body' => "Hi {$firstName}, your Privasee subscription expires in {$daysLeft} days. Renew now to continue enjoying premium benefits!"
            ]);

            Log::info('Subscription reminder SMS sent successfully', [
                'phone' => $phoneNumber,
                'message_sid' => $message->sid
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send subscription reminder SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    protected function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-digit characters
        $cleaned = preg_replace('/\D/', '', $phoneNumber);
        
        // If it starts with 971 (UAE country code), use as is
        if (str_starts_with($cleaned, '971')) {
            return '+' . $cleaned;
        }
        
        // If it starts with 0, replace with 971
        if (str_starts_with($cleaned, '0')) {
            return '+971' . substr($cleaned, 1);
        }
        
        // If it's just the local number, add 971
        if (strlen($cleaned) === 9) {
            return '+971' . $cleaned;
        }
        
        // Default: assume it needs UAE country code
        return '+971' . $cleaned;
    }

    public function getDeliveryStatus(string $messageSid): ?array
    {
        try {
            $message = $this->client->messages($messageSid)->fetch();
            
            return [
                'status' => $message->status,
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage,
                'date_sent' => $message->dateSent,
                'date_updated' => $message->dateUpdated,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get SMS delivery status', [
                'message_sid' => $messageSid,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}