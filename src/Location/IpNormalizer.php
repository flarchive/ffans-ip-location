<?php

namespace FFans\IpLocation\Location;

class IpNormalizer
{
    public function normalize(?string $ipAddress): ?string
    {
        if ($ipAddress === null || ($ipAddress = trim($ipAddress)) === '') {
            return null;
        }

        if (! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return null;
        }

        $packed = inet_pton($ipAddress);

        if ($packed === false) {
            return null;
        }

        if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\0", 10)."\xff\xff") {
            return inet_ntop(substr($packed, 12)) ?: null;
        }

        return inet_ntop($packed) ?: null;
    }

    public function isPublic(string $ipAddress): bool
    {
        if (filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false) {
            return false;
        }

        // PHP's FILTER_FLAG_NO_RES_RANGE does not consistently classify all
        // IPv6 special-purpose ranges, notably the documentation prefix.
        foreach (['2001:db8::/32', 'fc00::/7', 'fe80::/10', 'ff00::/8'] as $range) {
            if ($this->inCidr($ipAddress, $range)) {
                return false;
            }
        }

        return true;
    }

    private function inCidr(string $ipAddress, string $cidr): bool
    {
        [$network, $prefixLength] = explode('/', $cidr, 2);
        $ip = inet_pton($ipAddress);
        $base = inet_pton($network);

        if ($ip === false || $base === false || strlen($ip) !== strlen($base)) {
            return false;
        }

        $bits = (int) $prefixLength;
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && substr($ip, 0, $bytes) !== substr($base, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainder)) & 0xff;

        return (ord($ip[$bytes]) & $mask) === (ord($base[$bytes]) & $mask);
    }
}
