# FFans IP 属地

[![许可证](https://img.shields.io/packagist/l/ffans/ip-location.svg?label=许可证)](https://opensource.org/license/mit) [![Flarum](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FFFans%2Fip-location%2F2.x%2Fcomposer.json&query=%24.require%5B%22flarum%2Fcore%22%5D&label=Flarum)](https://docs.flarum.org/) [![最新版本](https://img.shields.io/github/v/tag/FFans/ip-location?filter=v0.2.*&sort=semver&label=最新版本)](https://github.com/FFans/ip-location/releases) [![发布日期](https://img.shields.io/github/release-date/ffans/ip-location.svg?display_date=published_at&label=发布日期)](https://github.com/ffans/ip-location/releases/latest) [![总下载量](https://img.shields.io/packagist/dt/ffans/ip-location.svg?label=总下载量)](https://packagist.org/packages/ffans/ip-location/stats) [![月下载量](https://img.shields.io/packagist/dm/ffans/ip-location.svg?label=月下载量)](https://packagist.org/packages/ffans/ip-location/stats)

面向中国大陆互联网平台的 IP 属地合规展示。中国大陆 IP 仅展示省份名称，其他 IP 展示国家或地区名称。

属地会显示在帖子和用户主页中。用户主页展示当前访问者可见的最近一条评论帖的发布属地，可在扩展设置中单独关闭。

## 环境要求

- Flarum 2.x

## 安装

使用 Composer 安装：

```sh
composer require ffans/ip-location
php flarum migrate
php flarum cache:clear
```

在 Flarum 管理后台中启用本扩展。

## 更新

```sh
composer update ffans/ip-location
php flarum migrate
php flarum cache:clear
```

## 配置离线定位库

扩展内置 gzip 压缩的 IPv4 和 IPv6 XDB 数据库。启用扩展后，请在扩展设置页面点击“初始化定位库”。后端会将其安装到 Flarum storage 目录：

```text
storage/ffans-ip-location/ip2region_v4.xdb
storage/ffans-ip-location/ip2region_v6.xdb
```

定位库未初始化时无法解析 IP 地址，但不会阻止帖子发布。你也可以使用以下管理命令手动检查或安装定位库：

```bash
php flarum ffans-ip-location:database-status
php flarum ffans-ip-location:database-update
php flarum ffans-ip-location:backfill
```

- `database-status` 检查定位库安装状态。
- `database-update` 解压定位数据库，数据库数据随扩展版本更新。
- `backfill` 重新解析帖子的 IP 属地。默认只会解析没有 IP 属地信息的帖子。
  - 使用 `--force` 可强制重新解析全部帖子。
  - 此功能也可在扩展设置页面使用。

重新解析功能需要使用异步队列。Flarum 2.x 内置 `database` 队列，请在 `config.php` 中配置：

```php
'queue' => [
    'driver' => 'database',
],
```

并按 Flarum 官方说明定期执行 `php flarum schedule:run`。

或使用 Redis、Horizon 等异步队列扩展。

## 隐私政策

- 扩展不重新记录 IP，IP 数据来源于 Flarum Core 的记录。
- 扩展不会公开原始 IP。
- 不解析保存 IP 城市、运营商、经纬度或 ASN。
- 编辑帖子不会改变发布时记录的属地。
- 解析失败不会阻止内容发布。

IP 属地是网络层面的估计结果，可能受运营商调度、代理、VPN 和 CGNAT 影响。站点运营者仍需自行完善隐私政策、保存期限和其他合规措施。

## 重要说明

本扩展面向在中国大陆运营的互联网平台，提供 IP 属地展示功能，用于协助平台运营者在合理范围内展示 IP 地址归属地。默认展示策略对中国大陆 IP 展示省份名称，对其他国家或地区的 IP 仅展示国家或地区名称。

是否启用本扩展，由平台部署运营方根据自身业务场景、运营地区及适用法律自行决定。平台运营方应自行评估具体展示方式的适用性与合规性；本扩展不构成法律意见，亦不保证仅依靠启用本扩展即可满足全部合规要求。

本扩展适用于 Flarum 开源程序，不代表 Flarum 基金会对本扩展的功能、展示策略或地区称谓进行审核、认可或背书。

IP 属地解析数据来源于第三方数据源，可能存在定位误差、更新延迟或地区命名差异。扩展会基于预设展示策略对原始数据做格式化处理。使用者应根据实际业务需要完成校验与调整。


## 翻译

帮助翻译本扩展，请前往 [Robert Korulczyk's Weblate 平台](https://weblate.rob006.net/projects/flarum2/ffans-ip-location/)。

## 链接

- [GitHub](https://github.com/ffans/ip-location)
- [Packagist](https://packagist.org/packages/ffans/ip-location)
- [中文社区](https://discuss.flarum.org.cn/d/16551)

## 第三方代码

本扩展使用 ip2region 官方 PHP XDB 查询代码，以及 IPv4、IPv6 XDB 数据库快照。这些第三方内容继续采用 Apache License 2.0，不属于本项目 MIT 许可证的授权范围。

具体来源、版本、提交记录、文件路径及许可证信息，详见 [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md)。

## 许可证

除另有说明的第三方内容外，本项目代码采用 [MIT 许可证](LICENSE)。

本发行包同时包含采用 Apache License 2.0 的 ip2region 代码和数据库文件；相关版权声明及完整许可证见 [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) 和 [lib/ip2region/LICENSE.md](lib/ip2region/LICENSE.md)。
