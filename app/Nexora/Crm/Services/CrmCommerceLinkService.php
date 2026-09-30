<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Services;

use App\Models\CommerceCustomer;
use App\Models\CrmCommerceLink;
use App\Models\CrmContact;
use App\Models\CrmOrganization;
use App\Nexora\Crm\Contracts\CrmCommerceLinkContract;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CrmCommerceLinkService implements CrmCommerceLinkContract
{
    public function __construct(
        private CrmTimelineService $timeline,
        private TenantContext $tenant,
    ) {}

    public function link(CommerceCustomer $customer, ?CrmContact $contact = null, ?CrmOrganization $organization = null, ?int $actorId = null): CrmCommerceLink
    {
        if (! $contact && ! $organization) {
            throw new InvalidArgumentException('Choose a CRM contact or organization to link.');
        }

        $this->assertCurrentTenant($customer, $contact, $organization);

        if ($contact && $organization && $contact->organization_id !== null && $contact->organization_id !== $organization->id) {
            throw new InvalidArgumentException('The selected contact belongs to a different organization.');
        }

        return DB::transaction(function () use ($customer, $contact, $organization, $actorId): CrmCommerceLink {
            $link = CrmCommerceLink::query()->updateOrCreate(
                ['commerce_customer_id' => $customer->id],
                [
                    'contact_id' => $contact?->id,
                    'organization_id' => $organization?->id,
                    'linked_by' => $actorId,
                    'linked_at' => now(),
                ],
            );

            if ($contact) {
                $this->timeline->record('contact', $contact->id, 'commerce.customer_linked', 'Commerce customer linked', $customer->name, ['commerce_customer_id' => $customer->id], $actorId);
            }
            if ($organization) {
                $this->timeline->record('organization', $organization->id, 'commerce.customer_linked', 'Commerce customer linked', $customer->name, ['commerce_customer_id' => $customer->id], $actorId);
            }

            return $link;
        });
    }

    private function assertCurrentTenant(CommerceCustomer $customer, ?CrmContact $contact, ?CrmOrganization $organization): void
    {
        $tenantId = $this->tenant->id();
        if ($tenantId === null) {
            throw new InvalidArgumentException('A current organization is required before linking CRM and Commerce records.');
        }

        foreach ([
            'Commerce customer' => $customer,
            'CRM contact' => $contact,
            'CRM organization' => $organization,
        ] as $label => $model) {
            if ($model === null) {
                continue;
            }

            if ((string) $model->getAttribute('tenant_id') !== $tenantId) {
                throw new InvalidArgumentException("{$label} must belong to the current organization.");
            }
        }
    }
}
