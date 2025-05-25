<?php

namespace Aflorea4\NetopiaPayments\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Aflorea4\NetopiaPayments\Facades\NetopiaPayments;
use Aflorea4\NetopiaPayments\Events\NetopiaPaymentConfirmed;
use Aflorea4\NetopiaPayments\Events\NetopiaPaymentCanceled;
use Aflorea4\NetopiaPayments\Events\NetopiaPaymentPending;

class NetopiaPaymentController extends Controller
{
    /**
     * Handle the payment confirmation (IPN - Instant Payment Notification)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function confirm(Request $request)
    {
        try {
            // Get the env_key, data and iv from the request
            $envKey = $request->input('env_key');
            $data = $request->input('data');
            $iv = $request->input('iv');

            // Process the payment response
            $response = NetopiaPayments::processResponse($envKey, $data, $iv);

            // Log the payment response
            Log::info('Netopia payment response', [
                'order_id' => $response->orderId,
                'action' => $response->action,
                'processed_amount' => $response->processedAmount,
            ]);

            // Dispatch the appropriate event based on the payment status
            if ($response->isSuccessful()) {
                event(new NetopiaPaymentConfirmed($response));
            } elseif ($response->isPending()) {
                event(new NetopiaPaymentPending($response));
            } elseif ($response->isCanceled()) {
                event(new NetopiaPaymentCanceled($response));
            }

            // Return the payment response to Netopia
            return response(
                NetopiaPayments::generatePaymentResponse(),
                200,
                ['Content-Type' => 'application/xml']
            );
        } catch (Exception $e) {
            // Log the error
            Log::error('Netopia payment error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return an error response to Netopia
            return response(
                NetopiaPayments::generatePaymentResponse(1, 1, $e->getMessage()),
                200,
                ['Content-Type' => 'application/xml']
            );
        }
    }

    /**
     * Handle the payment return (redirect after payment)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function return(Request $request)
    {
        try {
            // Get the env_key, data and iv from the request
            $envKey = $request->input('env_key');
            $data = $request->input('data');
            $iv = $request->input('iv');

            // Process the payment response
            $response = NetopiaPayments::processResponse($envKey, $data, $iv);

            // Redirect based on the payment status
            if ($response->isSuccessful() || $response->isPaid()) {
                return redirect()->route('payment.success', ['order_id' => $response->orderId]);
            } elseif ($response->isPending()) {
                return redirect()->route('payment.pending', ['order_id' => $response->orderId]);
            } else {
                return redirect()->route('payment.failed', [
                    'order_id' => $response->orderId,
                    'error_code' => $response->errorCode,
                    'error_message' => $response->errorMessage,
                ]);
            }
        } catch (Exception $e) {
            // Log the error
            Log::error('Netopia payment return error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirect to the payment failed page
            return redirect()->route('payment.failed', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
