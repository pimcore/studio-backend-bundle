---
title: Notifications
description: Contribute notification types and delivery channels to the Studio notification framework.
---

# Extending Notifications

Studio users choose, per notification type, whether they are notified and through which channels.

The preferences screen is a grid, and the two concepts are its two axes:

- A **notification type** is one kind of event a user can subscribe to — one *row*: "New asset
  uploaded", "You were mentioned".
- A **delivery channel** is a way a notification reaches the user outside the app — one *column*:
  email, chat. (The in-app pop-up appears as a column too, but is built in.)

Bundles can contribute both:

| Extension point | Interface | Tag |
|---|---|---|
| Notification type | `NotificationTypeProviderInterface` | `pimcore.studio_backend.notification_type_provider` |
| Delivery channel | `ChannelInterface` | `pimcore.studio_backend.notification_channel` |

Implement the interface and register the service — tagging is automatic. A built-in catch-all type
(`info`) already covers untyped notifications, so add a type only when it should appear as its own
row on the preferences screen.

## Sending a Notification

Build a `DispatchableNotification` and hand it to the dispatcher. Every subscribed recipient gets a
bell entry and, per their preferences, an external delivery; unsubscribed recipients are skipped.

```php
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\DispatchableNotification;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\NotificationDispatcherInterface;

$this->dispatcher->dispatch(new DispatchableNotification(
    typeId: 'app_dam.new_asset',
    recipientIds: [42],
    title: 'New asset uploaded',
    message: 'A new image was added to "Campaigns".',
    senderId: 7,                  // optional; here: the uploader
    linkedElement: $asset,        // optional; drives the bell attachment
    payload: ['assetId' => 1234], // optional; handed to the frontend renderer
));
```

### Why a separate dispatcher?

Pimcore's own `NotificationService` writes the bell entry unconditionally — it knows nothing about
types, preferences or channels. The dispatcher is where those are enforced: it checks the
recipient's subscription **before** anything is written (an unsubscribed user simply gets no
entry), and then delivers to the channels that user enabled. Both paths write the same
`notifications` table and feed the same bell — the dispatcher is a front door, not a second system.

Use the dispatcher for anything that should be subscribable. Existing code that uses
`NotificationService` or writes the model directly keeps working unchanged; those notifications
fall into the built-in `info` type, which cannot be unsubscribed from — users can only turn its
pop-up off.

## Adding a Notification Type

A type is plain data — a `NotificationType` value object. Register a **provider** that returns
your bundle's types; the constructor defaults are the framework defaults, so you only state what
is specific to your type:

```php
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationType;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationTypeProviderInterface;

final class AppDamNotificationTypes implements NotificationTypeProviderInterface
{
    public function getTypes(): array
    {
        return [
            new NotificationType(
                typeId: 'app_dam.new_asset',
                translationKey: 'app_dam.notification.new_asset.label',
                descriptionKey: 'app_dam.notification.new_asset.description',
                group: 'app_dam',
                sortOrder: 10,
                allowsExternalDelivery: true, // opt in to transport channels (email, …)
            ),
        ];
    }
}
```

Defaults: subscribed, pop-up on, no external delivery. The remaining constructor arguments set
the initial state for a new user (`defaultChannels`, `subscribedByDefault`) and
`subscriptionLocked: true` forbids unsubscribing entirely.

:::warning
Type ids are persisted in `notifications.type`, a `VARCHAR(20)` column: **at most 20 characters**
and unique across all bundles (`app_dam.new_asset` is 17 and fits; `app_dam.new_asset_upload` is 24
and is rejected). They are also stored in subscription rows, so renaming one later is a breaking change.
:::

The preferences row is labelled from your two translation keys. The **group heading** is composed by
the frontend as `notifications.settings.group.<group>`, so ship that key in your bundle's
`translations/studio.<locale>.yaml` — group headings only appear once a second group exists, and a
missing key shows up as the raw key in place of the heading.

## Adding a Delivery Channel

A **channel** delivers a notification outside the bell — email is built in, chat could be yours.
Unlike a type, a channel is a real service rather than a value: it has behavior (`send()`, per-user
availability) and its own dependencies, so it stays an interface you implement:

```php
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\ChannelInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\UserInterface;

final class SlackChannel implements ChannelInterface
{
    public function getName(): string   { return 'slack'; }   // 'popup' is reserved
    public function getSortOrder(): int { return 200; }        // column order on the preferences screen

    // Null when you can reach them. A translation key when you cannot — no linked account, no
    // address — and the preferences screen explains the switch instead of leaving it silent.
    public function unavailableReasonFor(UserInterface $recipient): ?string
    {
        return $this->slackIdFor($recipient) === null ? 'app.channel.slack.not-linked' : null;
    }

    public function send(Notification $notification, UserInterface $recipient): void
    {
        // Must not block on the network — queue it (dispatch a Messenger message and deliver from
        // the handler) so a slow endpoint never slows the action that produced the notification.
    }
}
```

- A channel is offered to every type whose `allowsExternalDelivery()` is `true` — types never
  enumerate channels, so a new channel lights up existing types without touching them.
- It defaults **off** per user: installing a bundle never silently starts emailing people.
- When no registered type allows external delivery, transport channels are not offered at all.

## Configuration

```yaml
pimcore_studio_backend:
    notifications:
        channels:
            email:
                enabled: true   # disabling removes the channel from the preferences screen entirely
        email:
            # Your own Twig template, or override the default in place at
            # templates/bundles/PimcoreStudioBackendBundle/notification/email.html.twig.
            # Receives: title, message, link, name, locale.
            template: '@PimcoreStudioBackend/notification/email.html.twig'
```

## Frontend Rendering

By default a typed notification renders as its title and message in the bell. To render it richer,
register a renderer on the `DynamicTypeNotificationRegistry` in **pimcore/studio-ui-bundle** (see its
notification module); it receives the `payload` you passed to `DispatchableNotification`.

:::warning
The payload is published over Mercure on a topic every signed-in Studio client subscribes to. Keep it
to the identifiers a renderer needs — nothing a recipient's colleagues should not see.
:::
