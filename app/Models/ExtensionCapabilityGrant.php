<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class ExtensionCapabilityGrant extends Model { protected $table='nx_extension_capability_grants'; protected $guarded=[]; protected function casts():array{return['granted'=>'boolean','granted_at'=>'datetime','revoked_at'=>'datetime'];} public function extension():BelongsTo{return $this->belongsTo(Extension::class,'extension_id');} }
