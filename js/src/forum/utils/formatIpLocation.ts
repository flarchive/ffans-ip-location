import app from 'flarum/forum/app';

export interface IpLocationData {
  status: 'resolved' | 'unknown' | 'private' | 'invalid' | 'failed';
  countryCode?: string | null;
  subdivisionCode?: string | null;
}

export default function formatIpLocation(location: IpLocationData): string {
  if (location.status !== 'resolved') {
    return app.translator.trans('ffans-ip-location.forum.unknown') as string;
  }

  if (location.countryCode === 'CN' && location.subdivisionCode) {
    const key = `ffans-ip-location.lib.subdivisions.${location.subdivisionCode}`;
    const translated = app.translator.trans(key);

    if (translated !== key) return translated as string;
  }

  if (location.countryCode && ['HK', 'MO', 'TW'].includes(location.countryCode)) {
    return app.translator.trans(`ffans-ip-location.lib.regions.${location.countryCode}`) as string;
  }

  if (location.countryCode) {
    try {
      const displayNames = new Intl.DisplayNames([app.data.locale], { type: 'region' });
      const name = displayNames.of(location.countryCode);

      if (name) return name;
    } catch {}
  }

  return app.translator.trans('ffans-ip-location.forum.unknown') as string;
}
