<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if ($schema->hasTable('ffans_ip_location_recalculations')) {
            return;
        }

        $schema->create('ffans_ip_location_recalculations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status', 16)->index();
            $table->unsignedTinyInteger('active_key')->nullable()->unique();
            $table->unsignedInteger('requested_by')->nullable();
            $table->unsignedInteger('max_post_id')->default(0);
            $table->unsignedInteger('last_post_id')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->text('counts')->nullable();
            $table->text('error')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('ffans_ip_location_recalculations');
    },
];

