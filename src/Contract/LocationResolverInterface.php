<?php

namespace FFans\IpLocation\Contract;

use FFans\IpLocation\Location\RawLocation;

interface LocationResolverInterface
{
    public function resolve(string $ipAddress): RawLocation;

    public function databaseStatus(): array;
}
