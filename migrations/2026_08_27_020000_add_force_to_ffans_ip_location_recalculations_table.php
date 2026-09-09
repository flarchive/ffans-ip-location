<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if (! $schema->hasColumn('ffans_ip_location_recalculations', 'force')) {
            $schema->table('ffans_ip_location_recalculations', function (Blueprint $table) {
                $table->boolean('force')->default(false)->after('requested_by');
            });
        }
    },
    'down' => function (Builder $schema) {
        if ($schema->hasColumn('ffans_ip_location_recalculations', 'force')) {
            $schema->table('ffans_ip_location_recalculations', function (Blueprint $table) {
                $table->dropColumn('force');
            });
        }
    },
];
