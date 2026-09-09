<?php

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Extend;
use Flarum\Post\Event\Posted;
use Flarum\Post\Post;
use FFans\IpLocation\Api\UserResourceFields;
use FFans\IpLocation\Api\ForumResourceFields;
use FFans\IpLocation\Api\PostResourceFields;
use FFans\IpLocation\Api\Controller\InitializeDatabaseController;
use FFans\IpLocation\Api\Controller\RecalculateLocationsController;
use FFans\IpLocation\Api\Controller\ShowRecalculationController;
use FFans\IpLocation\Console\BackfillLocationsCommand;
use FFans\IpLocation\Console\DatabaseStatusCommand;
use FFans\IpLocation\Console\DatabaseUpdateCommand;
use FFans\IpLocation\IpLocationServiceProvider;
use FFans\IpLocation\Listener\ResolvePostedLocation;
use FFans\IpLocation\PostLocation;

return [
    (new Extend\ServiceProvider())
        ->register(IpLocationServiceProvider::class),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('api'))
        ->post('/ffans-ip-location/database', 'ffans-ip-location.database.initialize', InitializeDatabaseController::class)
        ->get('/ffans-ip-location/recalculate', 'ffans-ip-location.recalculate.show', ShowRecalculationController::class)
        ->post('/ffans-ip-location/recalculate', 'ffans-ip-location.recalculate.start', RecalculateLocationsController::class),

    (new Extend\Model(Post::class))
        ->hasOne('ipLocation', PostLocation::class, 'post_id'),

    (new Extend\Event())
        ->listen(Posted::class, ResolvePostedLocation::class),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->fields(PostResourceFields::class)
        ->endpoint(
            [Endpoint\Create::class, Endpoint\Update::class, Endpoint\Show::class, Endpoint\Index::class],
            fn ($endpoint) => $endpoint->eagerLoad(['ipLocation'])
        ),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(UserResourceFields::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(ForumResourceFields::class),

    (new Extend\Settings())
        ->default('ffans-ip-location.enabled', '1')
        ->default('ffans-ip-location.show_unknown', '0')
        ->default('ffans-ip-location.display_position', 'footer')
        ->default('ffans-ip-location.display_on_profile', '1')
        ->serializeToForum('ffansIpLocationEnabled', 'ffans-ip-location.enabled', fn ($value) => $value === '1')
        ->serializeToForum('ffansIpLocationShowUnknown', 'ffans-ip-location.show_unknown', fn ($value) => $value === '1')
        ->serializeToForum('ffansIpLocationDisplayPosition', 'ffans-ip-location.display_position')
        ->serializeToForum('ffansIpLocationDisplayOnProfile', 'ffans-ip-location.display_on_profile', fn ($value) => $value === '1'),

    (new Extend\Console())
        ->command(BackfillLocationsCommand::class)
        ->command(DatabaseStatusCommand::class)
        ->command(DatabaseUpdateCommand::class),
];
