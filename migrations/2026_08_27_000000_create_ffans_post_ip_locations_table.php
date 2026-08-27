<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if ($schema->hasTable('ffans_post_ip_locations')) {
            return;
        }

        $schema->create('ffans_post_ip_locations', function (Blueprint $table) {
            $table->unsignedInteger('post_id')->primary();
            $table->string('status', 16);
            $table->char('country_code', 2)->nullable();
            $table->string('subdivision_code', 8)->nullable();
            $table->string('country_name', 96)->nullable();
            $table->string('subdivision_name', 96)->nullable();
            $table->string('provider', 32)->nullable();
            $table->string('database_version', 128)->nullable();
            $table->dateTime('resolved_at');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index('status');
            $table->index(['country_code', 'subdivision_code'], 'ffans_ip_location_region_index');
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('ffans_post_ip_locations');
    },
];
