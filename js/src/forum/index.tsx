import app from 'flarum/forum/app';
import Extend from 'flarum/common/extenders';
import { extend as extendComponent } from 'flarum/common/extend';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import CommentPost from 'flarum/forum/components/CommentPost';
import PostMeta from 'flarum/forum/components/PostMeta';
import UserCard from 'flarum/forum/components/UserCard';
import UserPage from 'flarum/forum/components/UserPage';
import IpLocationIcon from './components/IpLocationIcon';
import IpLocationLabel, { IpLocationData } from './components/IpLocationLabel';
import formatIpLocation from './utils/formatIpLocation';

export const extend = [
  new Extend.Model(Post).attribute<IpLocationData | null>('ipLocation'),
  new Extend.Model(User).attribute<IpLocationData | null>('ipLocation'),
];

app.initializers.add('ffans-ip-location', () => {
  const pendingProfileLocations = new Set<string>();
  const loadProfileLocation = (user: User) => {
    if (!app.forum.attribute<boolean>('ffansIpLocationDisplayOnProfile')) return;
    if (Object.prototype.hasOwnProperty.call(user.data.attributes || {}, 'ipLocation')) return;

    const userId = user.id();

    if (!userId || pendingProfileLocations.has(userId)) return;

    pendingProfileLocations.add(userId);
    m.redraw();

    const finishLoading = () => {
      pendingProfileLocations.delete(userId);
      m.redraw();
    };

    app.store.find<User>('users', userId).then(finishLoading, finishLoading);
  };

  extendComponent(UserPage.prototype, 'show', function (_, user) {
    loadProfileLocation(user);
  });

  extendComponent(UserCard.prototype, 'oninit', function () {
    const attrs = this.attrs as { className?: string; user: User };
    const classes = String(attrs.className || '').split(/\s+/);

    if (classes.includes('UserCard--popover')) {
      loadProfileLocation(attrs.user);
    }
  });

  extendComponent(PostMeta.prototype, 'viewItems', function (items) {
    if (app.forum.attribute<string>('ffansIpLocationDisplayPosition') !== 'header') return;

    const post = this.attrs.post;

    if (!(post instanceof Post)) return;

    const location = post.attribute<IpLocationData | null>('ipLocation');

    if (location) {
      // `time` has priority 100 and the dropdown has priority 90, so this
      // renders directly after the publication date and before edited status.
      items.add('ipLocation', <IpLocationLabel location={location} plain />, 95);
    }
  });

  extendComponent(CommentPost.prototype, 'footerItems', function (items) {
    if (app.forum.attribute<string>('ffansIpLocationDisplayPosition') === 'header') return;

    const location = this.attrs.post.attribute<IpLocationData | null>('ipLocation');

    if (location) {
      items.add('ipLocation', <IpLocationLabel location={location} />, -20);
    }
  });

  extendComponent(UserCard.prototype, 'infoItems', function (items) {
    if (!app.forum.attribute<boolean>('ffansIpLocationDisplayOnProfile')) return;

    const attrs = this.attrs as { className?: string; user: User };
    const classes = String(attrs.className || '').split(/\s+/);

    if (!classes.includes('UserHero') && !classes.includes('UserCard--popover')) return;

    const user = attrs.user;
    const userId = user.id();
    // FoF User Bio uses -100. Read it dynamically when available so the
    // location remains immediately before item-bio regardless of load state.
    const priority = items.has('bio') ? items.getPriority('bio') + 1 : -99;

    if (userId && pendingProfileLocations.has(userId)) {
      items.add(
        'ipLocation',
        <span className="UserCard-ipLocation UserCard-ipLocation--loading">
          <LoadingIndicator
            display="unset"
            size="small"
            containerClassName="UserCard-ipLocationSpinner"
          />
          {app.translator.trans('ffans-ip-location.forum.loading')}
        </span>,
        priority,
      );

      return;
    }

    const location = user.attribute<IpLocationData | null>('ipLocation');

    if (!location) return;

    const label = formatIpLocation(location);

    items.add(
      'ipLocation',
      <span
        className="UserCard-ipLocation"
        title={app.translator.trans('ffans-ip-location.forum.tooltip') as string}
      >
        <IpLocationIcon />
        {app.translator.trans('ffans-ip-location.forum.label', { location: label })}
      </span>,
      priority,
    );
  });
});
