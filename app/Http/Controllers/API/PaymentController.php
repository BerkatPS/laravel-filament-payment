<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Validate request parameters
            $request->validate([
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'status' => 'nullable|string|in:pending,completed,failed,refunded',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
            ]);

            $query = Payment::query();
            
            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('from_date')) {
                $query->whereDate('payment_date', '>=', $request->from_date);
            }
            
            if ($request->has('to_date')) {
                $query->whereDate('payment_date', '<=', $request->to_date);
            }
            
            // Paginate the results
            $perPage = $request->input('per_page', 15);
            $payments = $query->paginate($perPage);
            
            // Log this access for audit purposes
            Log::info('API payments list accessed', [
                'user_id' => $request->user()->id,
                'filters' => $request->only(['status', 'from_date', 'to_date']),
            ]);
            
            return response()->json([
                'data' => $payments->items(),
                'meta' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API error in payments listing', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'An error occurred while retrieving payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified payment.
     * 
     * @param Request $request
     * @param Payment $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Payment $payment)
    {
        try {
            // Log this access for audit purposes
            Log::info('API payment detail accessed', [
                'user_id' => $request->user()->id,
                'payment_id' => $payment->id,
            ]);
            
            return response()->json([
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            Log::error('API error in payment detail', [
                'user_id' => $request->user()->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'An error occurred while retrieving payment details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
