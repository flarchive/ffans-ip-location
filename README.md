# FFans IP Location

[![License](https://img.shields.io/packagist/l/ffans/ip-location.svg)](https://opensource.org/license/mit) [![Flarum](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FFFans%2Fip-location%2F2.x%2Fcomposer.json&query=%24.require%5B%22flarum%2Fcore%22%5D&label=Flarum)](https://docs.flarum.org/) [![Latest Version](https://img.shields.io/github/v/tag/FFans/ip-location?filter=v2.*&sort=semver&label=version)](https://github.com/FFans/ip-location/releases) [![Release Date](https://img.shields.io/github/release-date/ffans/ip-location.svg?display_date=published_at)](https://github.com/ffans/ip-location/releases/latest) [![Total Downloads](https://img.shields.io/packagist/dt/ffans/ip-location.svg)](https://packagist.org/packages/ffans/ip-location/stats) [![Monthly Downloads](https://img.shields.io/packagist/dm/ffans/ip-location.svg)](https://packagist.org/packages/ffans/ip-location/stats)

A [Flarum](http://flarum.org) extension. Compliant IP geolocation display for internet platforms serving Mainland China. For Mainland China, only provincial-level divisions are displayed; for other countries or regions, only the country or region name is displayed.

Locations are displayed on posts and user profiles. A user profile shows the publication location of the user's latest comment post visible to the current visitor, and this display can be disabled independently in the extension settings.

## Requirements

- Flarum 2.x

## Installation

Install with Composer:

```sh
composer require ffans/ip-location
php flarum migrate
php flarum cache:clear
```

You can enable the extension from the Flarum administration dashboard.

## Updating

```sh
composer update ffans/ip-location
php flarum migrate
php flarum cache:clear
```

## Configure the offline location databases

The extension bundles gzip-compressed IPv4 and IPv6 XDB databases. After enabling the extension, click **Initialize databases** on its settings page. The backend installs the databases into the Flarum storage directory:

```text
storage/ffans-ip-location/ip2region_v4.xdb
storage/ffans-ip-location/ip2region_v6.xdb
```

IP addresses cannot be resolved until the databases are initialized, but posting is never blocked. You can also inspect or install the databases manually with these commands:

```bash
php flarum ffans-ip-location:database-status
php flarum ffans-ip-location:database-update
php flarum ffans-ip-location:backfill
```

- `database-status` checks the installation status of the geolocation databases.
- `database-update` extracts the geolocation databases. Database data is updated with extension releases.
- `backfill` resolves post IP locations again. By default, it processes only posts without IP location information.
  - Use `--force` to resolve all posts again.
  - This feature is also available on the extension settings page.

Recalculation requires an asynchronous queue. Flarum 2.x includes the `database` queue driver. Configure it in `config.php` as follows:

```php
'queue' => [
    'driver' => 'database',
],
```

Then run `php flarum schedule:run` regularly as described in the official Flarum documentation.

Alternatively, use an asynchronous queue extension such as Redis or Horizon.

## Privacy policy

- The extension does not store IP addresses again; IP data comes from records maintained by Flarum Core.
- The extension never exposes raw IP addresses.
- It does not resolve or store cities, ISPs, coordinates, or ASN data.
- Editing a post does not change the location recorded when it was published.
- Lookup failures never prevent content from being published.

IP location is an estimate at the network level and may be affected by carrier routing, proxies, VPNs, and CGNAT. Site operators remain responsible for maintaining their own privacy policies, retention periods, and other compliance measures.

## Important Notes

This extension provides IP geolocation display functionality for internet platforms operating in Mainland China, to assist platform operators in showing IP‑derived location information within reasonable bounds. By default, it displays provincial‑level names for Mainland China IPs, and only country or region names for IPs from other countries or regions.

Whether to enable this extension is determined by the deploying platform operator based on their business context, operating territory, and applicable laws. Operators shall independently assess the suitability and compliance of display methods. This extension does not constitute legal advice, nor does it guarantee that enabling it alone will satisfy all compliance requirements.

This is a Flarum extension. It does not imply review, endorsement or approval of this extension’s features, display policies or regional naming by the Flarum Foundation.

IP geolocation resolution data comes from third‑party sources, which may contain location inaccuracies, update delays or variations in regional naming. The extension applies formatting processing to raw data according to its predefined display policy. Deploying operators shall perform verification and adjustments to fit their practical business needs.

## Translations

Want to help translate this extension? Visit [Robert Korulczyk's Weblate](https://weblate.rob006.net/projects/flarum2/ffans-ip-location/).

## Links

- [GitHub](https://github.com/ffans/ip-location)
- [Packagist](https://packagist.org/packages/ffans/ip-location)
- [Discuss in Chinese](https://discuss.flarum.org.cn/d/16551)

## Third-party code

This extension uses the official ip2region PHP XDB lookup code, along with IPv4 and IPv6 XDB database snapshots. These third‑party contents are licensed under Apache License 2.0 and are not covered by this project’s MIT license.

For detailed sources, versions, commit records, file paths and license information, please refer to [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## License

Except for third‑party contents otherwise noted, the code of this project is released under the [MIT License](LICENSE).

This distribution also includes ip2region code and database files licensed under Apache License 2.0. Relevant copyright notices and full license texts can be found in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) and [lib/ip2region/LICENSE.md](lib/ip2region/LICENSE.md).
