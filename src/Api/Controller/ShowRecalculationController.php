<?php

namespace FFans\IpLocation\Api\Controller;

use Flarum\Foundation\ApplicationInfoProvider;
use Flarum\Http\RequestUtil;
use FFans\IpLocation\Recalculation;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowRecalculationController implements RequestHandlerInterface
{
    public function __construct(protected ApplicationInfoProvider $appInfo)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $queueDriver = $this->appInfo->identifyQueueDriver();
        $task = Recalculation::query()->latest('id')->first();

        return new JsonResponse($task?->apiData($queueDriver) ?? [
            'status' => 'idle',
            'total' => 0,
            'processed' => 0,
            'counts' => [],
            'force' => false,
            'queueDriver' => $queueDriver,
            'queueReady' => $queueDriver !== 'sync',
        ]);
    }
}
