<?php

namespace FFans\IpLocation\Api\Controller;

use Carbon\Carbon;
use Flarum\Foundation\ApplicationInfoProvider;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use FFans\IpLocation\BackfillPostQuery;
use FFans\IpLocation\Job\RecalculateLocationsJob;
use FFans\IpLocation\Recalculation;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\QueryException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class RecalculateLocationsController implements RequestHandlerInterface
{
    public function __construct(
        protected Queue $queue,
        protected ApplicationInfoProvider $appInfo,
        protected BackfillPostQuery $postQuery,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();
        $queueDriver = $this->appInfo->identifyQueueDriver();

        if ($queueDriver === 'sync') {
            throw new ValidationException([
                'queue' => 'An asynchronous queue driver is required. Configure the Flarum database queue and scheduler first.',
            ]);
        }

        $body = $request->getParsedBody();
        $force = is_array($body) && ($body['force'] ?? false) === true;

        $task = Recalculation::query()->whereNotNull('active_key')->latest('id')->first();

        if (! $task) {
            $posts = $this->postQuery->query($force);
            $maxPostId = (int) ((clone $posts)->max('id') ?? 0);
            $task = new Recalculation();
            $task->status = 'pending';
            $task->active_key = 1;
            $task->requested_by = $actor->id;
            $task->force = $force;
            $task->max_post_id = $maxPostId;
            $task->last_post_id = 0;
            $task->total = (clone $posts)->where('id', '<=', $maxPostId)->count();
            $task->processed = 0;
            $task->counts = [];
            try {
                $task->save();
            } catch (QueryException $e) {
                // active_key is unique, so simultaneous administrators converge
                // on the task that won the race instead of creating duplicates.
                $task = Recalculation::query()->whereNotNull('active_key')->latest('id')->first();

                if (! $task) {
                    throw $e;
                }
            }

            if ($task->wasRecentlyCreated) {
                try {
                    $this->queue->push(new RecalculateLocationsJob($task->id));
                } catch (Throwable $e) {
                    $task->status = 'failed';
                    $task->active_key = null;
                    $task->error = mb_substr($e->getMessage(), 0, 2000);
                    $task->finished_at = Carbon::now();
                    $task->save();

                    throw $e;
                }
            }
        }

        return new JsonResponse($task->fresh()->apiData($queueDriver), 202);
    }
}
