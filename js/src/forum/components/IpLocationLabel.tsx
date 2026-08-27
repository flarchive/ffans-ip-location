import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import formatIpLocation, { IpLocationData } from '../utils/formatIpLocation';

export type { IpLocationData } from '../utils/formatIpLocation';

interface Attrs extends ComponentAttrs {
  location: IpLocationData;
  plain?: boolean;
}

export default class IpLocationLabel extends Component<Attrs> {
  view(): Mithril.Children {
    const label = formatIpLocation(this.attrs.location);
    const plain = this.attrs.plain === true;

    return (
      <span
        className="Post-ipLocation"
        title={app.translator.trans('ffans-ip-location.forum.tooltip') as string}
      >
        {plain
          ? label
          : app.translator.trans('ffans-ip-location.forum.published_in', { location: label })}
      </span>
    );
  }
}
