<?php

namespace FFans\IpLocation;

use Flarum\Foundation\AbstractServiceProvider;
use FFans\IpLocation\Contract\LocationResolverInterface;
use FFans\IpLocation\Resolver\XdbResolver;

class IpLocationServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(XdbResolver::class);
        $this->container->alias(XdbResolver::class, LocationResolverInterface::class);
    }
}
