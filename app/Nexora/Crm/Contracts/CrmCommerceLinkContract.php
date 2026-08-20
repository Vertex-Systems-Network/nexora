<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Contracts;

use App\Models\CommerceCustomer;
use App\Models\CrmCommerceLink;
use App\Models\CrmContact;
use App\Models\CrmOrganization;

interface CrmCommerceLinkContract
{
    public function link(CommerceCustomer $customer, ?CrmContact $contact = null, ?CrmOrganization $organization = null, ?int $actorId = null): CrmCommerceLink;
}
