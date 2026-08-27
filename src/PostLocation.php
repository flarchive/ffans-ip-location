<?php

namespace FFans\IpLocation;

use Flarum\Database\AbstractModel;

/**
 * @property int $post_id
 * @property string $status
 * @property string|null $country_code
 * @property string|null $subdivision_code
 * @property string|null $country_name
 * @property string|null $subdivision_name
 * @property string|null $provider
 * @property string|null $database_version
 * @property \Carbon\Carbon $resolved_at
 */
class PostLocation extends AbstractModel
{
    protected $table = 'ffans_post_ip_locations';

    protected $primaryKey = 'post_id';

    public $incrementing = false;

    public $timestamps = true;

    protected $casts = [
        'post_id' => 'integer',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
