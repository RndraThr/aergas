<?php

namespace App\Providers;

// Tambahkan use statement untuk Model dan Policy Anda
use App\Models\SkData;
use App\Models\SrData;
use App\Policies\SkDataPolicy;
use App\Policies\SrDataPolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Daftarkan policy Anda di sini
        SkData::class => SkDataPolicy::class,
        SrData::class => SrDataPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
