<?php

namespace FFans\IpLocation\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use FFans\IpLocation\Contract\LocationResolverInterface;

class ForumResourceFields
{
    public function __construct(protected LocationResolverInterface $resolver)
    {
    }

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('ffansIpLocationDatabaseReadyV4')
                ->visible(fn (mixed $_, Context $context) => $context->getActor()->isAdmin())
                ->get(fn () => (bool) $this->resolver->databaseStatus()['ipv4']['ready']),
            Schema\Boolean::make('ffansIpLocationDatabaseReadyV6')
                ->visible(fn (mixed $_, Context $context) => $context->getActor()->isAdmin())
                ->get(fn () => (bool) $this->resolver->databaseStatus()['ipv6']['ready']),
        ];
    }
}
