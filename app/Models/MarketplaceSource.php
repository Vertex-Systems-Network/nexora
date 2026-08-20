<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class MarketplaceSource extends Model { use HasUuids; protected $table='nx_marketplace_sources'; protected $guarded=[]; public $incrementing=false; protected $keyType='string'; protected function casts():array{return['trusted_publishers_only'=>'boolean','last_synced_at'=>'datetime'];} public function items():HasMany{return $this->hasMany(MarketplaceCatalogItem::class,'source_id');} }
