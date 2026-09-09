<?php

namespace FFans\IpLocation;

use Flarum\Post\CommentPost;
use Illuminate\Database\Eloquent\Builder;

class BackfillPostQuery
{
    public function query(bool $force): Builder
    {
        $query = CommentPost::query()->orderBy('id');

        if (! $force) {
            $query->where(function (Builder $query) {
                $query
                    ->whereDoesntHave('ipLocation')
                    ->orWhereHas('ipLocation', function (Builder $query) {
                        $query->whereIn('status', ['failed', 'unknown']);
                    });
            });
        }

        return $query;
    }
}
