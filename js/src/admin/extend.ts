import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import DatabaseControl from './components/DatabaseControl';
import RecalculateControl from './components/RecalculateControl';

export default [
  new Extend.Admin()
    .setting(
      () => ({
        setting: 'ffans-ip-location.display_on_profile',
        type: 'boolean',
        label: app.translator.trans('ffans-ip-location.admin.settings.display_on_profile_label'),
        help: app.translator.trans('ffans-ip-location.admin.settings.display_on_profile_help'),
      }),
      100,
    )
    .setting(
      () => ({
        setting: 'ffans-ip-location.enabled',
        type: 'boolean',
        label: app.translator.trans('ffans-ip-location.admin.settings.enabled_label'),
        help: app.translator.trans('ffans-ip-location.admin.settings.enabled_help'),
      }),
      90,
    )
    .setting(
      () => ({
        setting: 'ffans-ip-location.display_position',
        type: 'select',
        options: {
          footer: app.translator.trans('ffans-ip-location.admin.settings.position_options.footer'),
          header: app.translator.trans('ffans-ip-location.admin.settings.position_options.header'),
        },
        label: app.translator.trans('ffans-ip-location.admin.settings.position_label'),
      }),
      80,
    )
    .setting(
      () => ({
        setting: 'ffans-ip-location.show_unknown',
        type: 'boolean',
        label: app.translator.trans('ffans-ip-location.admin.settings.show_unknown_label'),
        help: app.translator.trans('ffans-ip-location.admin.settings.show_unknown_help'),
      }),
      70,
    )
    .customSetting(() => m(DatabaseControl), 60)
    .customSetting(() => m(RecalculateControl), 50),
];
