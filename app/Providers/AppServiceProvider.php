<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Route;
use App\Services\TelegramService;
use App\Services\OpenAIService;
use App\Services\GoogleDriveService;
use App\Services\PhotoApprovalService;
use App\Services\NotificationService;
use App\Services\FileUploadService;
use App\Models\GasInData;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register AERGAS services as singletons
        $this->app->singleton(TelegramService::class, function ($app) {
            return new TelegramService();
        });

        $this->app->singleton(OpenAIService::class, function ($app) {
            return new OpenAIService();
        });

        $this->app->singleton(GoogleDriveService::class, function ($app) {
            return new GoogleDriveService();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        $this->app->singleton(FileUploadService::class, function ($app) {
            // Berikan GoogleDriveService yang sudah terdaftar ke dalam FileUploadService
            return new FileUploadService(
                $app->make(GoogleDriveService::class)
            );
        });

        $this->app->singleton(PhotoApprovalService::class, function ($app) {
            return new PhotoApprovalService(
                $app->make(TelegramService::class),
                $app->make(OpenAIService::class),
                $app->make(NotificationService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Route model binding for Gas In Data
        Route::model('gasIn', GasInData::class);
        
        // Custom validation rules for AERGAS
        Validator::extend('reff_id_format', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[A-Z]{3}[0-9]{3,}$/', $value);
        });

        Validator::extend('indonesian_phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^(\+62|62|0)[0-9]{8,13}$/', $value);
        });

        Validator::extend('safe_filename', function ($attribute, $value, $parameters, $validator) {
            return !preg_match('/[<>:"/\\|?*]/', $value);
        });

        // Custom validation messages
        Validator::replacer('reff_id_format', function ($message, $attribute, $rule, $parameters) {
            return 'The ' . $attribute . ' must be in format: AER001, CLP001, etc.';
        });

        Validator::replacer('indonesian_phone', function ($message, $attribute, $rule, $parameters) {
            return 'The ' . $attribute . ' must be a valid Indonesian phone number.';
        });

        Validator::replacer('safe_filename', function ($message, $attribute, $rule, $parameters) {
            return 'The ' . $attribute . ' contains invalid characters.';
        });
    }
}
