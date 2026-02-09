<?php

namespace App\Providers;

use App\Models\GovernanceItem;
use App\Models\Risk;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkItem;
use App\Policies\GovernanceItemPolicy;
use App\Policies\RiskPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkItemPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        WorkItem::class => WorkItemPolicy::class,
        GovernanceItem::class => GovernanceItemPolicy::class,
        Supplier::class => SupplierPolicy::class,
        Risk::class => RiskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
