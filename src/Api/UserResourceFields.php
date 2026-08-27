<?php

namespace FFans\IpLocation\Api;

use Flarum\Api\Context;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

class UserResourceFields
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
                ->visible(fn (User $user, Context $context) => $this->isProfileDisplayEnabled() && $context->showing(UserResource::class))
                ->get(function (User $user, Context $context): ?array {
                    /** @var CommentPost|null $post */
                    $post = CommentPost::query()
                        ->whereVisibleTo($context->getActor())
                        ->where('user_id', $user->id)
                        ->with('ipLocation')
                        ->orderByDesc('created_at')
                        ->orderByDesc('id')
                        ->first();

                    return $post ? $this->serializer->serialize($post, $context) : null;
                }),
        ];
    }

    private function isProfileDisplayEnabled(): bool
    {
        return $this->settings->get('ffans-ip-location.enabled', '1') === '1'
            && $this->settings->get('ffans-ip-location.display_on_profile', '1') === '1';
    }
}
