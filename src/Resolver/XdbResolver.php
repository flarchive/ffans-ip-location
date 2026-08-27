<?php

namespace FFans\IpLocation\Resolver;

use Flarum\Foundation\Paths;
use FFans\IpLocation\Contract\LocationResolverInterface;
use FFans\IpLocation\Location\RawLocation;
use ip2region\xdb\IPv4;
use ip2region\xdb\IPv6;
use ip2region\xdb\Searcher;
use ip2region\xdb\Util;
use RuntimeException;

class XdbResolver implements LocationResolverInterface
{
    public function __construct(protected Paths $paths)
    {
    }

    public function resolve(string $ipAddress): RawLocation
    {
        $version = str_contains($ipAddress, ':') ? 6 : 4;
        $path = $this->databasePath($version);

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("IPv$version XDB database is not readable.");
        }

        $error = Util::verifyFromFile($path);

        if ($error !== null) {
            throw new RuntimeException("Invalid IPv$version XDB database: $error");
        }

        $searcher = Searcher::newWithFileOnly(
            $version === 6 ? IPv6::default() : IPv4::default(),
            $path
        );

        try {
            $region = $searcher->search($ipAddress);
        } finally {
            $searcher->close();
        }

        if ($region === '') {
            return new RawLocation('', provider: 'ip2region', databaseVersion: $this->databaseVersion($path));
        }

        $parts = array_pad(explode('|', $region), 5, '');
        $countryCode = preg_match('/^[A-Za-z]{2}$/', $parts[4]) ? strtoupper($parts[4]) : null;

        return new RawLocation(
            $parts[0],
            $parts[1],
            $parts[2],
            $parts[3],
            $countryCode,
            'ip2region',
            $this->databaseVersion($path),
        );
    }

    public function databaseStatus(): array
    {
        return [
            'provider' => 'ip2region',
            'ipv4' => $this->fileStatus(4),
            'ipv6' => $this->fileStatus(6),
        ];
    }

    public function databasePath(int $version): string
    {
        return $this->paths->storage."/ffans-ip-location/ip2region_v$version.xdb";
    }

    private function fileStatus(int $version): array
    {
        $path = $this->databasePath($version);
        $exists = is_file($path) && is_readable($path);
        $error = $exists ? Util::verifyFromFile($path) : 'missing';

        return [
            'path' => $path,
            'ready' => $exists && $error === null,
            'error' => $error,
            'size' => $exists ? filesize($path) : null,
            'modifiedAt' => $exists ? filemtime($path) : null,
        ];
    }

    private function databaseVersion(string $path): string
    {
        return basename($path).'@'.(filemtime($path) ?: 0);
    }
}
