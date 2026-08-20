<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HelpdeskMessage extends Model
{
    use HasUuids;
    protected $table='nx_helpdesk_messages'; protected $guarded=[]; public $incrementing=false; protected $keyType='string';
    protected function casts(): array { return ['is_internal'=>'boolean','metadata'=>'array']; }
    public function ticket(): BelongsTo { return $this->belongsTo(HelpdeskTicket::class,'ticket_id'); }
    public function authorUser(): BelongsTo { return $this->belongsTo(User::class,'author_user_id'); }
}
