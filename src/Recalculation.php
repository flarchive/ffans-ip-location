<?php

namespace FFans\IpLocation;

use Flarum\Database\AbstractModel;

class Recalculation extends AbstractModel
{
    protected $table = 'ffans_ip_location_recalculations';

    public $timestamps = true;

    protected $casts = [
        'active_key' => 'integer',
        'requested_by' => 'integer',
        'force' => 'boolean',
        'max_post_id' => 'integer',
        'last_post_id' => 'integer',
        'total' => 'integer',
        'processed' => 'integer',
        'counts' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function apiData(string $queueDriver): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total' => $this->total,
            'processed' => $this->processed,
            'counts' => $this->counts ?? [],
            'force' => (bool) $this->force,
            'error' => $this->status === 'failed' ? $this->error : null,
            'createdAt' => $this->created_at?->toIso8601String(),
            'startedAt' => $this->started_at?->toIso8601String(),
            'finishedAt' => $this->finished_at?->toIso8601String(),
            'queueDriver' => $queueDriver,
            'queueReady' => $queueDriver !== 'sync',
        ];
    }
}
