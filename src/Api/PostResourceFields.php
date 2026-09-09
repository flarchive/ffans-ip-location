<?php

namespace FFans\IpLocation\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;

class PostResourceFields
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected LocationDataSerializer $serializer,
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Arr::make('ipLocation')
                ->nullable()
                ->visible(fn (Post $post) => $post instanceof CommentPost && $this->isEnabled())
                ->get(fn (Post $post, Context $context) => $this->serializer->serialize($post, $context)),
        ];
    }

    private function isEnabled(): bool
    {
        return $this->settings->get('ffans-ip-location.enabled', '1') === '1';
    }
}
