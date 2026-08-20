<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class MarketplaceCatalogItem extends Model { use HasUuids; protected $table='nx_marketplace_catalog_items'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts():array{return['metadata'=>'array','synced_at'=>'datetime'];} public function source():BelongsTo{return $this->belongsTo(MarketplaceSource::class,'source_id');} }
