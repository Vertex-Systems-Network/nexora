<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Support;

use App\Models\CrmContact;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\CrmOrganization;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class CrmEntityTypes
{
    /** @return array<string,class-string<Model>> */
    public static function models(): array
    {
        return [
            'organization'=>CrmOrganization::class,
            'contact'=>CrmContact::class,
            'lead'=>CrmLead::class,
            'opportunity'=>CrmOpportunity::class,
        ];
    }

    public static function assert(string $type): string
    {
        $type=strtolower(trim($type));
        if (! isset(self::models()[$type])) throw new InvalidArgumentException('Unsupported CRM entity type: '.$type.'.');
        return $type;
    }

    public static function findOrFail(string $type, string $id): Model
    {
        $type=self::assert($type);
        $class=self::models()[$type];
        return $class::query()->findOrFail($id);
    }
}
