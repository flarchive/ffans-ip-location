<?php

namespace FFans\IpLocation\Location;

final readonly class RawLocation
{
    public function __construct(
        public string $country,
        public string $subdivision = '',
        public string $city = '',
        public string $isp = '',
        public ?string $countryCode = null,
        public string $provider = 'ip2region',
        public ?string $databaseVersion = null,
    ) {
    }
}
