<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SystemInfoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:super_admin'), // Hanya super admin yang bisa akses
        ];
    }

    /**
     * Display PHP information page
     */
    public function phpinfo()
    {
        // Security check - pastikan user adalah super admin
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403, 'Access denied. Super admin required.');
        }

        // Generate phpinfo output
        ob_start();
        phpinfo();
        $phpinfo = ob_get_clean();

        // Style the output dengan custom CSS untuk branding
        $styledPhpinfo = $this->stylePhpinfo($phpinfo);

        return response($styledPhpinfo)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Get system information as JSON (untuk API/debugging)
     */
    public function systemInfo()
    {
        if (!auth()->user()->hasRole('super_admin')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        return response()->json([
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'loaded_extensions' => get_loaded_extensions(),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'database' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
        ]);
    }

    /**
     * Style phpinfo output untuk branding AERGAS
     */
    private function stylePhpinfo(string $phpinfo): string
    {
        // Replace default title dan tambah warning
        $phpinfo = str_replace(
            '<title>phpinfo()</title>',
            '<title>AERGAS - System Information (CONFIDENTIAL)</title>',
            $phpinfo
        );

        // Tambah warning banner di atas
        $warningBanner = '
        <div style="background: #dc2626; color: white; padding: 15px; text-align: center; font-weight: bold; margin: 10px;">
            ⚠️ CONFIDENTIAL SYSTEM INFORMATION - SUPER ADMIN ACCESS ONLY ⚠️
            <br>
            <small>User: ' . auth()->user()->name . ' (' . auth()->user()->username . ') | Time: ' . now()->format('Y-m-d H:i:s') . '</small>
        </div>';

        // Insert warning setelah <body>
        $phpinfo = str_replace('<body>', '<body>' . $warningBanner, $phpinfo);

        // Tambah AERGAS branding
        $aergarsBranding = '
        <div style="background: #1e40af; color: white; padding: 10px; text-align: center; margin: 10px;">
            <h2>🏢 AERGAS System Information</h2>
            <p>Environment: ' . config('app.env') . ' | Laravel: ' . app()->version() . '</p>
        </div>';

        $phpinfo = str_replace($warningBanner, $warningBanner . $aergarsBranding, $phpinfo);

        return $phpinfo;
    }
}