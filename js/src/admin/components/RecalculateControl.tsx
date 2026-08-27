import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Component from 'flarum/common/Component';
import Switch from 'flarum/common/components/Switch';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';

interface RecalculationStatus {
  id?: number;
  status: 'idle' | 'pending' | 'running' | 'completed' | 'failed';
  processed: number;
  total: number;
  counts: Record<string, number>;
  error?: string | null;
  queueDriver: string;
  queueReady: boolean;
  force: boolean;
  createdAt?: string | null;
  startedAt?: string | null;
  finishedAt?: string | null;
}

export default class RecalculateControl extends Component {
  private loading = true;
  private starting = false;
  private task: RecalculationStatus | null = null;
  private pollTimer?: number;
  private notifyWhenFinished = false;
  private force = false;

  oninit(): void {
    void this.refresh(true);
  }

  onremove(): void {
    if (this.pollTimer) window.clearTimeout(this.pollTimer);
  }

  view(): Mithril.Children {
    const databaseReady = this.databaseReady();

    return (
      <div className="Form-group ffansIpLocation-recalculate">
        <label>{app.translator.trans('ffans-ip-location.admin.recalculate.title')}</label>
        <div className="helpText">
          {app.translator.trans('ffans-ip-location.admin.recalculate.help')}
        </div>
        <Switch
          state={this.force}
          disabled={this.loading || this.isActive()}
          onchange={(force: boolean) => (this.force = force)}
        >
          {app.translator.trans('ffans-ip-location.admin.recalculate.force_label')}
          <div className="helpText">
            {app.translator.trans('ffans-ip-location.admin.recalculate.force_help')}
          </div>
        </Switch>
        <Button
          className="Button Button--primary"
          icon="fas fa-rotate"
          loading={this.loading || this.starting}
          disabled={
            this.loading || this.isActive() || this.task?.queueReady === false || !databaseReady
          }
          onclick={() => this.start()}
        >
          {app.translator.trans('ffans-ip-location.admin.recalculate.button')}
        </Button>
        {this.task?.queueReady === false && (
          <div className="Alert Alert--warning ffansIpLocation-recalculateNotice">
            {app.translator.trans('ffans-ip-location.admin.recalculate.queue_required', {
              driver: this.task.queueDriver,
              a: (
                <a
                  href="https://docs.flarum.org/scheduler"
                  target="_blank"
                  rel="noopener noreferrer"
                />
              ),
            })}
          </div>
        )}
        {!databaseReady && (
          <div className="Alert Alert--warning ffansIpLocation-recalculateNotice">
            {app.translator.trans('ffans-ip-location.admin.recalculate.database_required')}
          </div>
        )}
        {this.task && this.task.status !== 'idle' && (
          <span className="ffansIpLocation-recalculateProgress">
            {this.taskSummary(this.task)}
            {this.task.status === 'pending' && (
              <i
                className="fas fa-spinner fa-spin ffansIpLocation-recalculateSpinner"
                aria-hidden="true"
              />
            )}
          </span>
        )}
      </div>
    );
  }

  private async start(): Promise<void> {
    const confirmKey = this.force ? 'confirm_force' : 'confirm_normal';

    if (
      !confirm(
        extractText(app.translator.trans(`ffans-ip-location.admin.recalculate.${confirmKey}`)),
      )
    )
      return;

    this.starting = true;
    m.redraw();

    try {
      this.task = await app.request<RecalculationStatus>({
        url: `${app.forum.attribute('apiUrl')}/ffans-ip-location/recalculate`,
        method: 'POST',
        body: { force: this.force },
      });
      this.notifyWhenFinished = true;
      this.schedulePoll();
    } catch (error) {
      app.alerts.show(
        { type: 'error' },
        app.translator.trans('ffans-ip-location.admin.recalculate.error'),
      );
    } finally {
      this.starting = false;
      m.redraw();
    }
  }

  private async refresh(initial = false): Promise<void> {
    try {
      const previousStatus = this.task?.status;
      this.task = await app.request<RecalculationStatus>({
        url: `${app.forum.attribute('apiUrl')}/ffans-ip-location/recalculate`,
        method: 'GET',
      });

      if (initial && this.isActive()) this.notifyWhenFinished = true;

      if (this.isActive()) this.force = this.task.force;

      if (this.isActive()) {
        this.schedulePoll();
      } else if (
        this.notifyWhenFinished &&
        previousStatus &&
        ['pending', 'running'].includes(previousStatus)
      ) {
        this.notifyWhenFinished = false;
        const successful = this.task.status === 'completed';

        app.alerts.show(
          { type: successful ? 'success' : 'error' },
          successful
            ? app.translator.trans('ffans-ip-location.admin.recalculate.success', {
                count: this.task.processed,
              })
            : app.translator.trans('ffans-ip-location.admin.recalculate.error'),
        );
      }
    } catch (error) {
      if (!initial) this.schedulePoll();
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  private schedulePoll(): void {
    if (this.pollTimer) window.clearTimeout(this.pollTimer);
    this.pollTimer = window.setTimeout(() => void this.refresh(), 2000);
  }

  private isActive(): boolean {
    return this.task?.status === 'pending' || this.task?.status === 'running';
  }

  private databaseReady(): boolean {
    return Boolean(
      app.forum.attribute('ffansIpLocationDatabaseReadyV4') &&
      app.forum.attribute('ffansIpLocationDatabaseReadyV6'),
    );
  }

  private taskSummary(task: RecalculationStatus): Mithril.Children {
    const key = `ffans-ip-location.admin.recalculate.summary.${task.status}`;

    if (task.status === 'completed' || task.status === 'failed') {
      return app.translator.trans(key, {
        time: this.formatTime(task.finishedAt ?? task.createdAt),
        processed: task.processed,
        total: task.total,
      });
    }

    return app.translator.trans(key, {
      processed: task.processed,
      total: task.total,
    });
  }

  private formatTime(value?: string | null): string {
    if (!value) {
      return extractText(app.translator.trans('ffans-ip-location.admin.recalculate.unknown_time'));
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat(app.data.locale, {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(date);
  }
}
