import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Component from 'flarum/common/Component';
import type Mithril from 'mithril';

interface DatabaseInitializationResult {
  ready: boolean;
  ipv4Ready: boolean;
  ipv6Ready: boolean;
}

export default class DatabaseControl extends Component {
  private initializing = false;

  view(): Mithril.Children {
    const ipv4Ready = this.ipv4Ready();
    const ipv6Ready = this.ipv6Ready();
    const ready = ipv4Ready && ipv6Ready;

    return (
      <div className="Form-group ffansIpLocation-database">
        <label>{app.translator.trans('ffans-ip-location.admin.database.title')}</label>
        <div className="helpText">
          {app.translator.trans('ffans-ip-location.admin.database.help')}
        </div>

        <div
          className={`Alert ${ready ? 'Alert--success' : 'Alert--warning'} ffansIpLocation-databaseNotice`}
        >
          {app.translator.trans(
            ready
              ? 'ffans-ip-location.admin.database.ready'
              : 'ffans-ip-location.admin.database.not_ready',
          )}
        </div>

        <div className="ffansIpLocation-databaseFamilies">
          {app.translator.trans('ffans-ip-location.admin.database.family_status', {
            ipv4: app.translator.trans(
              `ffans-ip-location.admin.database.${ipv4Ready ? 'available' : 'missing'}`,
            ),
            ipv6: app.translator.trans(
              `ffans-ip-location.admin.database.${ipv6Ready ? 'available' : 'missing'}`,
            ),
          })}
        </div>

        <Button
          className={`Button ${ready ? '' : 'Button--primary'}`}
          icon="fas fa-database"
          loading={this.initializing}
          disabled={this.initializing}
          onclick={() => this.initialize()}
        >
          {app.translator.trans(
            this.initializing
              ? 'ffans-ip-location.admin.database.initializing'
              : ready
                ? 'ffans-ip-location.admin.database.reinitialize_button'
                : 'ffans-ip-location.admin.database.initialize_button',
          )}
        </Button>
      </div>
    );
  }

  private ipv4Ready(): boolean {
    return Boolean(app.forum.attribute('ffansIpLocationDatabaseReadyV4'));
  }

  private ipv6Ready(): boolean {
    return Boolean(app.forum.attribute('ffansIpLocationDatabaseReadyV6'));
  }

  private async initialize(): Promise<void> {
    this.initializing = true;
    m.redraw();

    try {
      const result = await app.request<DatabaseInitializationResult>({
        url: `${app.forum.attribute('apiUrl')}/ffans-ip-location/database`,
        method: 'POST',
      });

      app.forum.pushAttributes({
        ffansIpLocationDatabaseReadyV4: result.ipv4Ready,
        ffansIpLocationDatabaseReadyV6: result.ipv6Ready,
      });

      app.alerts.show(
        { type: result.ready ? 'success' : 'error' },
        app.translator.trans(
          result.ready
            ? 'ffans-ip-location.admin.database.success'
            : 'ffans-ip-location.admin.database.error',
        ),
      );
    } catch (error) {
      app.alerts.show(
        { type: 'error' },
        app.translator.trans('ffans-ip-location.admin.database.error'),
      );
    } finally {
      this.initializing = false;
      m.redraw();
    }
  }
}
