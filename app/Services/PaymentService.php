<?php

namespace App\Services;

use App\Models\User;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\Payment;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Subscription as StripeSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $stripeKey;
    protected $telrConfig;

    public function __construct()
    {
        $this->stripeKey = config('services.stripe.secret');
        $this->telrConfig = config('services.telr');
        
        if ($this->stripeKey) {
            Stripe::setApiKey($this->stripeKey);
        }
    }

    public function createUserSubscription(User $user, string $planType, array $paymentData): array
    {
        return DB::transaction(function () use ($user, $planType, $paymentData) {
            // Get plan details
            $planDetails = $this->getSubscriptionPlan($planType);
            
            if (!$planDetails) {
                throw new \Exception('Invalid subscription plan');
            }

            // Create or get Stripe customer
            $stripeCustomer = $this->getOrCreateStripeCustomer($user);

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $planDetails['amount'] * 100, // Convert to cents
                'currency' => 'aed',
                'customer' => $stripeCustomer->id,
                'payment_method' => $paymentData['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'user_id' => $user->id,
                    'subscription_type' => $planType,
                    'plan_name' => $planDetails['name']
                ]
            ]);

            if ($paymentIntent->status === 'succeeded') {
                // Create subscription record
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'type' => $planType,
                    'status' => 'active',
                    'amount' => $planDetails['amount'],
                    'currency' => 'AED',
                    'starts_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'stripe_subscription_id' => null,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'metadata' => [
                        'plan_details' => $planDetails,
                        'payment_method' => $paymentData['payment_method_type'] ?? 'card'
                    ]
                ]);

                // Update user subscription status
                $user->update([
                    'subscription_type' => $planType,
                    'subscription_status' => 'active',
                    'subscription_expires_at' => $subscription->expires_at
                ]);

                // Create payment record
                Payment::create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $planDetails['amount'],
                    'currency' => 'AED',
                    'status' => 'completed',
                    'payment_method' => 'stripe',
                    'transaction_id' => $paymentIntent->id,
                    'metadata' => [
                        'stripe_payment_intent' => $paymentIntent->toArray()
                    ]
                ]);

                // Log activity
                activity()
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'subscription_type' => $planType,
                        'amount' => $planDetails['amount']
                    ])
                    ->log('Subscription created');

                return [
                    'success' => true,
                    'subscription' => $subscription,
                    'payment_intent' => $paymentIntent
                ];
            }

            throw new \Exception('Payment failed: ' . $paymentIntent->status);
        });
    }

    public function createBusinessSubscription(Business $business, string $planType, array $paymentData): array
    {
        return DB::transaction(function () use ($business, $planType, $paymentData) {
            $planDetails = $this->getBusinessSubscriptionPlan($planType);
            
            if (!$planDetails) {
                throw new \Exception('Invalid business subscription plan');
            }

            // Create Stripe customer for business
            $stripeCustomer = Customer::create([
                'email' => $business->email,
                'name' => $business->name,
                'metadata' => [
                    'business_id' => $business->id,
                    'type' => 'business'
                ]
            ]);

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $planDetails['amount'] * 100,
                'currency' => 'aed',
                'customer' => $stripeCustomer->id,
                'payment_method' => $paymentData['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'business_id' => $business->id,
                    'subscription_type' => $planType,
                    'plan_name' => $planDetails['name']
                ]
            ]);

            if ($paymentIntent->status === 'succeeded') {
                // Update business subscription
                $business->update([
                    'subscription_type' => $planType,
                    'subscription_status' => 'active',
                    'subscription_expires_at' => now()->addMonth()
                ]);

                // Create payment record
                Payment::create([
                    'business_id' => $business->id,
                    'amount' => $planDetails['amount'],
                    'currency' => 'AED',
                    'status' => 'completed',
                    'payment_method' => 'stripe',
                    'transaction_id' => $paymentIntent->id,
                    'metadata' => [
                        'stripe_payment_intent' => $paymentIntent->toArray(),
                        'plan_details' => $planDetails
                    ]
                ]);

                return [
                    'success' => true,
                    'payment_intent' => $paymentIntent
                ];
            }

            throw new \Exception('Payment failed: ' . $paymentIntent->status);
        });
    }

    public function cancelUserSubscription(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $activeSubscription = $user->subscriptions()
                ->where('status', 'active')
                ->first();

            if (!$activeSubscription) {
                throw new \Exception('No active subscription found');
            }

            // Cancel Stripe subscription if exists
            if ($activeSubscription->stripe_subscription_id) {
                $stripeSubscription = StripeSubscription::retrieve($activeSubscription->stripe_subscription_id);
                $stripeSubscription->cancel();
            }

            // Update subscription status
            $activeSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            // Update user status
            $user->update([
                'subscription_status' => 'cancelled'
            ]);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->log('Subscription cancelled');

            return true;
        });
    }

    public function processRefund(Payment $payment, float $amount = null): array
    {
        if ($payment->status !== 'completed') {
            throw new \Exception('Payment is not eligible for refund');
        }

        $refundAmount = $amount ?? $payment->amount;

        if ($refundAmount > $payment->amount) {
            throw new \Exception('Refund amount cannot exceed payment amount');
        }

        try {
            // Process Stripe refund
            $refund = \Stripe\Refund::create([
                'payment_intent' => $payment->transaction_id,
                'amount' => $refundAmount * 100, // Convert to cents
                'metadata' => [
                    'payment_id' => $payment->id,
                    'refund_reason' => 'Customer request'
                ]
            ]);

            // Update payment record
            $payment->update([
                'refunded_amount' => ($payment->refunded_amount ?? 0) + $refundAmount,
                'status' => $refundAmount >= $payment->amount ? 'refunded' : 'partially_refunded'
            ]);

            // Log activity
            activity()
                ->performedOn($payment)
                ->log('Payment refunded', [
                    'refund_amount' => $refundAmount,
                    'stripe_refund_id' => $refund->id
                ]);

            return [
                'success' => true,
                'refund' => $refund,
                'refunded_amount' => $refundAmount
            ];
        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Refund processing failed: ' . $e->getMessage());
        }
    }

    protected function getOrCreateStripeCustomer(User $user): Customer
    {
        // Check if user already has a Stripe customer ID
        if ($user->stripe_customer_id) {
            try {
                return Customer::retrieve($user->stripe_customer_id);
            } catch (\Exception $e) {
                // Customer not found, create new one
            }
        }

        // Create new Stripe customer
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->full_name,
            'phone' => $user->phone,
            'metadata' => [
                'user_id' => $user->id,
                'type' => 'user'
            ]
        ]);

        // Save customer ID to user
        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    protected function getSubscriptionPlan(string $planType): ?array
    {
        $plans = [
            'basic' => [
                'name' => 'Basic Plan',
                'amount' => 99,
                'features' => ['Access to basic venues', 'Limited offers']
            ],
            'premium' => [
                'name' => 'Premium Plan',
                'amount' => 199,
                'features' => ['Access to all venues', 'Unlimited offers', 'Priority booking']
            ],
            'vip' => [
                'name' => 'VIP Plan',
                'amount' => 399,
                'features' => ['All premium features', 'Exclusive venues', 'Personal concierge']
            ]
        ];

        return $plans[$planType] ?? null;
    }

    protected function getBusinessSubscriptionPlan(string $planType): ?array
    {
        $plans = [
            'basic' => [
                'name' => 'Business Basic',
                'amount' => 299,
                'features' => ['List up to 3 venues', 'Basic analytics']
            ],
            'premium' => [
                'name' => 'Business Premium',
                'amount' => 599,
                'features' => ['Unlimited venues', 'Advanced analytics', 'Featured listings']
            ],
            'enterprise' => [
                'name' => 'Business Enterprise',
                'amount' => 999,
                'features' => ['All premium features', 'Custom integrations', 'Dedicated support']
            ]
        ];

        return $plans[$planType] ?? null;
    }

    public function handleStripeWebhook(array $payload): void
    {
        $event = $payload['type'];
        $data = $payload['data']['object'];

        switch ($event) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($data);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($data);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionCancelled($data);
                break;
            default:
                Log::info('Unhandled Stripe webhook event', ['event' => $event]);
        }
    }

    protected function handlePaymentSucceeded(array $data): void
    {
        $payment = Payment::where('transaction_id', $data['id'])->first();
        
        if ($payment && $payment->status === 'pending') {
            $payment->update(['status' => 'completed']);
            
            Log::info('Payment completed via webhook', ['payment_id' => $payment->id]);
        }
    }

    protected function handlePaymentFailed(array $data): void
    {
        $payment = Payment::where('transaction_id', $data['id'])->first();
        
        if ($payment) {
            $payment->update(['status' => 'failed']);
            
            Log::warning('Payment failed via webhook', ['payment_id' => $payment->id]);
        }
    }

    protected function handleSubscriptionCancelled(array $data): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $data['id'])->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);
            
            Log::info('Subscription cancelled via webhook', ['subscription_id' => $subscription->id]);
        }
    }
}