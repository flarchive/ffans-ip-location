<?php

namespace FFans\IpLocation;

use Carbon\Carbon;
use Flarum\Post\Post;
use FFans\IpLocation\Contract\LocationResolverInterface;
use FFans\IpLocation\Location\ChinaLocationNormalizer;
use FFans\IpLocation\Location\IpNormalizer;
use FFans\IpLocation\Location\LocationResult;
use Throwable;

class LocationManager
{
    public function __construct(
        protected IpNormalizer $ipNormalizer,
        protected LocationResolverInterface $resolver,
        protected ChinaLocationNormalizer $locationNormalizer,
    ) {
    }

    public function resolveForPost(Post $post, bool $force = false): PostLocation
    {
        $existing = PostLocation::find($post->id);

        if ($existing && ! $force && ! in_array($existing->status, ['failed', 'unknown'], true)) {
            return $existing;
        }

        $ipAddress = $this->ipNormalizer->normalize($post->ip_address);

        if ($ipAddress === null) {
            $result = LocationResult::unresolved('invalid');
        } elseif (! $this->ipNormalizer->isPublic($ipAddress)) {
            $result = LocationResult::unresolved('private');
        } else {
            try {
                $result = $this->locationNormalizer->normalize($this->resolver->resolve($ipAddress));
            } catch (Throwable) {
                $result = LocationResult::unresolved('failed');
            }
        }

        $location = $existing ?? new PostLocation();
        $location->post_id = $post->id;
        $location->status = $result->status;
        $location->country_code = $result->countryCode;
        $location->subdivision_code = $result->subdivisionCode;
        $location->country_name = $result->countryName;
        $location->subdivision_name = $result->subdivisionName;
        $location->provider = $result->provider;
        $location->database_version = $result->databaseVersion;
        $location->resolved_at = Carbon::now();
        $location->save();

        $post->setRelation('ipLocation', $location);

        return $location;
    }
}
