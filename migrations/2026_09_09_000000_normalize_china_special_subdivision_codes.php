<?php

use Illuminate\Database\Schema\Builder;

$specialSubdivisions = [
    'HK' => '香港特别行政区',
    'MO' => '澳门特别行政区',
    'TW' => '台湾省',
];

return [
    'up' => function (Builder $schema) use ($specialSubdivisions) {
        if (! $schema->hasTable('ffans_post_ip_locations')) {
            return;
        }

        foreach ($specialSubdivisions as $code => $name) {
            $schema->getConnection()
                ->table('ffans_post_ip_locations')
                ->where('country_code', $code)
                ->update([
                    'country_code' => 'CN',
                    'subdivision_code' => $code,
                    'country_name' => '中国',
                    'subdivision_name' => $name,
                ]);
        }
    },
    'down' => function (Builder $schema) use ($specialSubdivisions) {
        if (! $schema->hasTable('ffans_post_ip_locations')) {
            return;
        }

        foreach ($specialSubdivisions as $code => $name) {
            $schema->getConnection()
                ->table('ffans_post_ip_locations')
                ->where('country_code', 'CN')
                ->where('subdivision_code', $code)
                ->update([
                    'country_code' => $code,
                    'subdivision_code' => null,
                    'country_name' => $name,
                    'subdivision_name' => null,
                ]);
        }
    },
];
