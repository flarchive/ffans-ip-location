<?php

namespace FFans\IpLocation\Api;

use Flarum\Api\Context;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use FFans\IpLocation\PostLocation;

class LocationDataSerializer
{
    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function serialize(Post $post, Context $context): ?array
    {
        /** @var PostLocation|null $location */
        $location = $post->relationLoaded('ipLocation')
            ? $post->getRelation('ipLocation')
            : $post->ipLocation;

        if (! $location) {
            return null;
        }

        $showUnknown = $this->settings->get('ffans-ip-location.show_unknown', '0') === '1';

        if ($location->status !== 'resolved' && ! $showUnknown) {
            return null;
        }

        $canViewStatus = $context->getActor()->can('viewIps', $post);
        $data = [
            'status' => $canViewStatus || $location->status === 'resolved' ? $location->status : 'unknown',
        ];

        if ($location->status === 'resolved') {
            if ($location->country_code !== null) {
                $data['countryCode'] = $location->country_code;
            }

            if ($location->subdivision_code !== null) {
                $data['subdivisionCode'] = $location->subdivision_code;
            }
        }

        return $data;
    }
}
