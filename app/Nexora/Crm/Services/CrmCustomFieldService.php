<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Services;

use App\Models\CrmCustomFieldDefinition;
use App\Models\CrmCustomFieldValue;
use App\Nexora\Crm\Support\CrmEntityTypes;
use InvalidArgumentException;

final class CrmCustomFieldService
{
    /** @param mixed $value */
    public function set(CrmCustomFieldDefinition $field, string $entityType, string $entityId, mixed $value): CrmCustomFieldValue
    {
        $entityType=CrmEntityTypes::assert($entityType);
        if ($field->entity_type !== $entityType || ! $field->active) throw new InvalidArgumentException('This custom field is not available for the selected CRM record.');
        CrmEntityTypes::findOrFail($entityType,$entityId);
        $normalized=$this->normalize($field,$value);
        return CrmCustomFieldValue::query()->updateOrCreate(['field_id'=>$field->id,'entity_type'=>$entityType,'entity_id'=>$entityId],['value'=>$normalized]);
    }

    /** @return array<string,mixed> */
    private function normalize(CrmCustomFieldDefinition $field, mixed $value): array
    {
        $type=(string)$field->field_type;
        if ($field->required && ($value===null || $value==='' || $value===[])) throw new InvalidArgumentException($field->label.' is required.');
        return match($type){
            'number'=>['value'=>$value===''||$value===null?null:(float)$value],
            'checkbox'=>['value'=>(bool)$value],
            'multi_select'=>['value'=>array_values(array_map('strval',is_array($value)?$value:[]))],
            default=>['value'=>$value===null?null:(string)$value],
        };
    }
}
