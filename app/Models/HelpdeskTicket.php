<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class HelpdeskTicket extends Model
{ use BelongsToTenant;
    use HasUuids;
    protected $table='nx_helpdesk_tickets'; protected $guarded=[]; public $incrementing=false; protected $keyType='string';
    protected function casts(): array { return ['first_response_due_at'=>'datetime','resolution_due_at'=>'datetime','first_responded_at'=>'datetime','resolved_at'=>'datetime','closed_at'=>'datetime','first_response_breached'=>'boolean','resolution_breached'=>'boolean','metadata'=>'array']; }
    public function requesterUser(): BelongsTo { return $this->belongsTo(User::class,'requester_user_id'); }
    public function requesterContact(): BelongsTo { return $this->belongsTo(CrmContact::class,'requester_contact_id'); }
    public function commerceCustomer(): BelongsTo { return $this->belongsTo(CommerceCustomer::class,'commerce_customer_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class,'assigned_to'); }
    public function slaPolicy(): BelongsTo { return $this->belongsTo(HelpdeskSlaPolicy::class,'sla_policy_id'); }
    public function messages(): HasMany { return $this->hasMany(HelpdeskMessage::class,'ticket_id')->orderBy('created_at'); }
    public function events(): HasMany { return $this->hasMany(HelpdeskTicketEvent::class,'ticket_id')->orderBy('occurred_at'); }
}
