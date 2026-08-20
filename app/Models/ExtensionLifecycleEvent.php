<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class ExtensionLifecycleEvent extends Model { use HasUuids; protected $table='nx_extension_lifecycle_events'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; public $timestamps=false; protected function casts():array{return['context'=>'array','created_at'=>'datetime'];} public function extension():BelongsTo{return $this->belongsTo(Extension::class,'extension_id');} public function version():BelongsTo{return $this->belongsTo(ExtensionVersion::class,'extension_version_id');} }
