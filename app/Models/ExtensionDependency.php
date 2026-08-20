<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class ExtensionDependency extends Model { protected $table='nx_extension_dependencies'; protected $guarded=[]; protected function casts():array{return['optional'=>'boolean'];} public function version():BelongsTo{return $this->belongsTo(ExtensionVersion::class,'extension_version_id');} }
