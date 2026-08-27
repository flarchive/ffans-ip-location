<?php

namespace FFans\IpLocation\Job;

use Carbon\Carbon;
use Flarum\Queue\AbstractJob;
use FFans\IpLocation\BackfillPostQuery;
use FFans\IpLocation\LocationManager;
use FFans\IpLocation\Recalculation;
use Illuminate\Contracts\Queue\Queue;
use Throwable;

class RecalculateLocationsJob extends AbstractJob
{
    private const BATCH_SIZE = 100;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $recalculationId)
    {
        parent::__construct();
    }

    public function handle(LocationManager $manager, BackfillPostQuery $postQuery, Queue $queue): void
    {
        $task = Recalculation::find($this->recalculationId);

        if (! $task || ! in_array($task->status, ['pending', 'running'], true)) {
            return;
        }

        if ($task->status === 'pending') {
            $task->status = 'running';
            $task->started_at = Carbon::now();
            $task->save();
        }

        $posts = $postQuery->query($task->force)
            ->where('id', '>', $task->last_post_id)
            ->where('id', '<=', $task->max_post_id)
            ->orderBy('id')
            ->limit(self::BATCH_SIZE + 1)
            ->get();
        $hasMore = $posts->count() > self::BATCH_SIZE;
        $posts = $posts->take(self::BATCH_SIZE);
        $counts = $task->counts ?? [];

        foreach ($posts as $post) {
            $location = $manager->resolveForPost($post, $task->force);
            $counts[$location->status] = ($counts[$location->status] ?? 0) + 1;
            $task->last_post_id = $post->id;
            $task->processed++;
        }

        $task->counts = $counts;
        $task->save();

        if ($hasMore) {
            $queue->push(new self($task->id));

            return;
        }

        $task->status = 'completed';
        $task->active_key = null;
        $task->finished_at = Carbon::now();
        $task->save();
    }

    public function failed(Throwable $exception): void
    {
        $task = Recalculation::find($this->recalculationId);

        if (! $task || ! in_array($task->status, ['pending', 'running'], true)) {
            return;
        }

        $task->status = 'failed';
        $task->active_key = null;
        $task->error = mb_substr($exception->getMessage(), 0, 2000);
        $task->finished_at = Carbon::now();
        $task->save();
    }
}
