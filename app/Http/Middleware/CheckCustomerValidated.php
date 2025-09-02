<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CalonPelanggan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CheckCustomerValidated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module = null): Response
    {
        // Skip validation for super admins and admins
        if (in_array(Auth::user()->role ?? '', ['super_admin', 'admin'])) {
            return $next($request);
        }

        // Extract reff_id from route parameters
        $reffId = null;

        // Try common route parameter names
        foreach (['reffId', 'reff_id_pelanggan', 'id', 'sk', 'sr', 'gasIn'] as $param) {
            if ($request->route($param)) {
                if (in_array($param, ['sk', 'sr', 'gasIn'])) {
                    // For module IDs, get the reff_id from the model
                    $model = match($param) {
                        'sk' => \App\Models\SkData::find($request->route($param)),
                        'sr' => \App\Models\SrData::find($request->route($param)),
                        'gasIn' => \App\Models\GasInData::find($request->route($param)),
                        default => null
                    };
                    $reffId = $model?->reff_id_pelanggan;
                } else {
                    $reffId = $request->route($param);
                }
                break;
            }
        }

        // If no reff_id found, check POST data
        if (!$reffId && $request->has('reff_id_pelanggan')) {
            $reffId = $request->input('reff_id_pelanggan');
        }

        // If still no reff_id, allow the request (might be listing pages)
        if (!$reffId) {
            return $next($request);
        }

        // Find customer
        $customer = CalonPelanggan::where('reff_id_pelanggan', $reffId)->first();

        if (!$customer) {
            Log::warning('Customer not found for validation check', [
                'reff_id' => $reffId,
                'route' => $request->route()->getName(),
                'user' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan tidak ditemukan'
                ], 404);
            }

            return redirect()->route('customers.index')
                           ->with('error', 'Pelanggan tidak ditemukan');
        }

        // Check if customer is validated
        if (!$customer->isValidated()) {
            Log::info('Access blocked: Customer not validated', [
                'reff_id' => $reffId,
                'customer_status' => $customer->status,
                'route' => $request->route()->getName(),
                'user' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan belum divalidasi. Silakan hubungi admin atau tracer untuk validasi.',
                    'redirect' => route('customers.show', $customer->reff_id_pelanggan)
                ], 422);
            }

            return redirect()->route('customers.show', $customer->reff_id_pelanggan)
                           ->with('warning', 'Pelanggan belum divalidasi. Silakan hubungi admin atau tracer untuk validasi.');
        }

        // If module is specified, check if customer can proceed to that module
        if ($module && !$customer->canProceedToModule($module)) {
            Log::info('Access blocked: Customer cannot proceed to module', [
                'reff_id' => $reffId,
                'module' => $module,
                'customer_status' => $customer->status,
                'progress_status' => $customer->progress_status,
                'route' => $request->route()->getName(),
                'user' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Belum bisa mengakses modul {$module}. Selesaikan modul sebelumnya terlebih dahulu.",
                    'redirect' => route('customers.show', $customer->reff_id_pelanggan)
                ], 422);
            }

            return redirect()->route('customers.show', $customer->reff_id_pelanggan)
                           ->with('warning', "Belum bisa mengakses modul {$module}. Selesaikan modul sebelumnya terlebih dahulu.");
        }

        return $next($request);
    }
}
