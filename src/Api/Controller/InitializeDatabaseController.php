<?php

namespace FFans\IpLocation\Api\Controller;

use Flarum\Http\RequestUtil;
use FFans\IpLocation\Contract\LocationResolverInterface;
use FFans\IpLocation\Database\BundledDatabaseInstaller;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class InitializeDatabaseController implements RequestHandlerInterface
{
    public function __construct(
        protected BundledDatabaseInstaller $installer,
        protected LocationResolverInterface $resolver,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $this->installer->installAll();
        $status = $this->resolver->databaseStatus();

        return new JsonResponse([
            'ready' => $status['ipv4']['ready'] && $status['ipv6']['ready'],
            'ipv4Ready' => $status['ipv4']['ready'],
            'ipv6Ready' => $status['ipv6']['ready'],
        ]);
    }
}
