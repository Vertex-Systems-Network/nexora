<?php

declare(strict_types=1);

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class CrmCommerceLink extends Model { use HasUuids; protected $table='nx_crm_commerce_links'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts():array{return['linked_at'=>'datetime'];} public function contact():BelongsTo{return $this->belongsTo(CrmContact::class,'contact_id');} public function organization():BelongsTo{return $this->belongsTo(CrmOrganization::class,'organization_id');} public function customer():BelongsTo{return $this->belongsTo(CommerceCustomer::class,'commerce_customer_id');} public function linkedBy():BelongsTo{return $this->belongsTo(User::class,'linked_by');} }
