<?php

declare(strict_types=1);

namespace App\Models;

use App\Nexora\Enterprise\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiGenerationRun extends Model
{
    use BelongsToTenant;

    protected $table = 'nx_ai_generation_runs';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'prompt_chars' => 'integer',
            'requested_output_tokens' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'output_chars' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function connection(): BelongsTo { return $this->belongsTo(AiConnection::class, 'ai_connection_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
