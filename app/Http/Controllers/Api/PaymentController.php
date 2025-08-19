<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\SubscribeRequest;
use App\Models\User;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        $this->middleware('auth:sanctum')->except(['stripeWebhook', 'telrWebhook']);
    }

    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $this->paymentService->createUserSubscription(
                $user,
                $request->plan_type,
                $request->validated()
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Subscription created successfully',
                    'data' => [
                        'subscription' => $result['subscription'],
                        'payment_intent' => $result['payment_intent']
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Subscription creation failed'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Subscription creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Subscription creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelSubscription(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $this->paymentService->cancelUserSubscription($user);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Subscription cancelled successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Subscription cancellation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Subscription cancellation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function paymentHistory(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $payments = Payment::where('user_id', $user->id)
                ->with(['subscription'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments->items(),
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'last_page' => $payments->lastPage(),
                        'per_page' => $payments->perPage(),
                        'total' => $payments->total(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function requestRefund(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'payment_id' => 'required|exists:payments,id',
                'amount' => 'nullable|numeric|min:0',
                'reason' => 'nullable|string|max:500'
            ]);

            $payment = Payment::where('id', $request->payment_id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            if (!$payment->canBeRefunded()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is not eligible for refund'
                ], 422);
            }

            $refundAmount = $request->amount ?? $payment->getRemainingRefundAmount();
            $result = $this->paymentService->processRefund($payment, $refundAmount);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'data' => [
                        'refund_amount' => $result['refunded_amount'],
                        'refund_id' => $result['refund']->id
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Refund processing failed'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Refund processing failed', [
                'user_id' => $request->user()->id,
                'payment_id' => $request->payment_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Refund processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function stripeWebhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->getContent();
            $sigHeader = $request->header('Stripe-Signature');
            $endpointSecret = config('services.stripe.webhook.secret');

            // Verify webhook signature
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $endpointSecret
                );
            } catch (\UnexpectedValueException $e) {
                Log::error('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid payload'], 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Log::error('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            // Handle the webhook event
            $this->paymentService->handleStripeWebhook($event->toArray());

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->getContent()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed'
            ], 500);
        }
    }

    public function telrWebhook(Request $request): JsonResponse
    {
        try {
            // Verify Telr webhook signature if needed
            $payload = $request->all();

            Log::info('Telr webhook received', ['payload' => $payload]);

            // Process Telr webhook based on their documentation
            // This is a placeholder implementation
            switch ($payload['status'] ?? null) {
                case 'paid':
                    $this->handleTelrPaymentSuccess($payload);
                    break;
                case 'cancelled':
                    $this->handleTelrPaymentCancelled($payload);
                    break;
                case 'declined':
                    $this->handleTelrPaymentDeclined($payload);
                    break;
                default:
                    Log::warning('Unknown Telr webhook status', ['payload' => $payload]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Telr webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed'
            ], 500);
        }
    }

    protected function handleTelrPaymentSuccess(array $payload): void
    {
        $transactionId = $payload['transaction_id'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)->first();

            if ($payment && $payment->status === 'pending') {
                $payment->update(['status' => 'completed']);

                Log::info('Telr payment completed', ['payment_id' => $payment->id]);
            }
        }
    }

    protected function handleTelrPaymentCancelled(array $payload): void
    {
        $transactionId = $payload['transaction_id'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)->first();

            if ($payment && $payment->status === 'pending') {
                $payment->update(['status' => 'cancelled']);

                Log::info('Telr payment cancelled', ['payment_id' => $payment->id]);
            }
        }
    }

    protected function handleTelrPaymentDeclined(array $payload): void
    {
        $transactionId = $payload['transaction_id'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)->first();

            if ($payment && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);

                Log::info('Telr payment declined', ['payment_id' => $payment->id]);
            }
        }
    }
}
