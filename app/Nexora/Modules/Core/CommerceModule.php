<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class CommerceModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier:'nexora.commerce', name:'Nexora Commerce & Billing', version:'0.30.0',
            description:'Provider-neutral catalog, pricing, customers, orders, invoices, taxes, refunds, subscriptions and billing event contracts.', core:true, loadOrder:62,
            capabilities:[
                'commerce.catalog.read','commerce.catalog.write','commerce.customers.read','commerce.customers.write',
                'commerce.orders.read','commerce.orders.write','commerce.billing.read','commerce.billing.write',
                'commerce.tax.manage','commerce.providers.register','commerce.payments.manage','admin.navigation.register',
            ],
            dependencies:[new ModuleDependency('nexora.admin','^0.5'),new ModuleDependency('nexora.automation','^0.27'),new ModuleDependency('nexora.extensions','^0.29')],
            metadata:['money_storage'=>'integer minor units','payment_gateways'=>'extension registered providers only','automatic_fx'=>false,'tax_engine'=>'explicit rules, no external tax claim'],
        );
    }

    public function register(): void
    {
        $this->navigation->register(['id'=>'commerce','label'=>'Commerce','href'=>'/admin/commerce','icon'=>'shopping-cart','order'=>74,'permission'=>'commerce.view']);
    }

    public function boot(): void {}
}
