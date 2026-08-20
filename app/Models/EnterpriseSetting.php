<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;
final class EnterpriseSetting extends Model { use HasUuids;protected $table='nx_enterprise_settings';protected $guarded=[];public $incrementing=false;protected $keyType='string';protected function casts():array{return ['value'=>'array'];} }
