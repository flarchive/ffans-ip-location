<?php

namespace FFans\IpLocation\Location;

class ChinaLocationNormalizer
{
    private const COUNTRY_CODES = [
        '中国' => 'CN', '中华人民共和国' => 'CN',
        '中国香港' => 'HK', '香港' => 'HK', '香港特别行政区' => 'HK',
        '中国澳门' => 'MO', '澳门' => 'MO', '澳门特别行政区' => 'MO',
        '中国台湾' => 'TW', '台湾' => 'TW', '台湾省' => 'TW',
        '美国' => 'US', '澳大利亚' => 'AU', '日本' => 'JP', '韩国' => 'KR',
        '新加坡' => 'SG', '英国' => 'GB', '德国' => 'DE', '法国' => 'FR',
        '加拿大' => 'CA', '俄罗斯' => 'RU', '印度' => 'IN',
    ];

    private const CHINA_SUBDIVISIONS = [
        '北京' => 'BJ', '天津' => 'TJ', '河北' => 'HE', '山西' => 'SX', '内蒙古' => 'NM',
        '辽宁' => 'LN', '吉林' => 'JL', '黑龙江' => 'HL', '上海' => 'SH', '江苏' => 'JS',
        '浙江' => 'ZJ', '安徽' => 'AH', '福建' => 'FJ', '江西' => 'JX', '山东' => 'SD',
        '河南' => 'HA', '湖北' => 'HB', '湖南' => 'HN', '广东' => 'GD', '广西' => 'GX',
        '海南' => 'HI', '重庆' => 'CQ', '四川' => 'SC', '贵州' => 'GZ', '云南' => 'YN',
        '西藏' => 'XZ', '陕西' => 'SN', '甘肃' => 'GS', '青海' => 'QH', '宁夏' => 'NX',
        '新疆' => 'XJ',
    ];

    public function normalize(RawLocation $raw): LocationResult
    {
        $country = $this->clean($raw->country);
        $subdivision = $this->clean($raw->subdivision);
        $countryCode = $raw->countryCode ? strtoupper($raw->countryCode) : null;

        if ($countryCode === null || ! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            $countryCode = self::COUNTRY_CODES[$country] ?? null;
        }

        if ($countryCode === 'CN') {
            $specialCode = self::COUNTRY_CODES[$subdivision] ?? null;

            if (in_array($specialCode, ['HK', 'MO', 'TW'], true)) {
                return new LocationResult(
                    'resolved',
                    $specialCode,
                    null,
                    $subdivision,
                    null,
                    $raw->provider,
                    $raw->databaseVersion
                );
            }
        }

        $subdivisionCode = $countryCode === 'CN'
            ? $this->chinaSubdivisionCode($subdivision)
            : null;

        if ($country === '' || $country === '0') {
            return LocationResult::unresolved('unknown');
        }

        return new LocationResult(
            'resolved',
            $countryCode,
            $subdivisionCode,
            $country,
            $countryCode === 'CN' && $subdivision !== '' && $subdivision !== '0' ? $subdivision : null,
            $raw->provider,
            $raw->databaseVersion
        );
    }

    private function chinaSubdivisionCode(string $name): ?string
    {
        foreach (self::CHINA_SUBDIVISIONS as $prefix => $code) {
            if (str_starts_with($name, $prefix)) {
                return $code;
            }
        }

        return null;
    }

    private function clean(string $value): string
    {
        return trim($value);
    }
}
