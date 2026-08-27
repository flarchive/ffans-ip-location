<?php

namespace FFans\IpLocation\Location;

final readonly class LocationResult
{
    public function __construct(
        public string $status,
        public ?string $countryCode = null,
        public ?string $subdivisionCode = null,
        public ?string $countryName = null,
        public ?string $subdivisionName = null,
        public ?string $provider = null,
        public ?string $databaseVersion = null,
    ) {
    }

    public static function unresolved(string $status): self
    {
        return new self($status);
    }
}
