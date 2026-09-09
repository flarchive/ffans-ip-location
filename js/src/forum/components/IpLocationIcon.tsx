import Component from 'flarum/common/Component';
import type Mithril from 'mithril';

export default class IpLocationIcon extends Component {
  view(): Mithril.Children {
    return (
      <svg
        className="UserCard-ipLocationIcon"
        viewBox="0 0 1024 1024"
        aria-hidden="true"
        focusable="false"
      >
        <circle cx="512" cy="512" r="416" fill="none" stroke="currentColor" stroke-width="112" />
        <rect x="284" y="328" width="112" height="368" rx="28" />
        <rect x="456" y="328" width="112" height="368" rx="28" />
        <path
          fill-rule="evenodd"
          d="M544 328h92c108 0 172 52 172 140s-64 140-172 140h-92V328zm24 92v96h68c40 0 60-16 60-48s-20-48-60-48h-68z"
        />
      </svg>
    );
  }
}
