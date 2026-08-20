<?php

declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;
final class EnterpriseAuditEvent extends Model { use HasUuids;protected $table='nx_enterprise_audit_events';protected $guarded=[];public $timestamps=false;public $incrementing=false;protected $keyType='string';protected function casts():array{return ['payload'=>'array','occurred_at'=>'datetime'];} }
