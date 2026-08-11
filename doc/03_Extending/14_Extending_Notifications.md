---
title: Notifications
description: Contribute notification types and delivery channels to the Studio notification framework.
---

# Extending Notifications

The Studio notification framework turns the notifications Pimcore writes into **typed, subscribable**
prompts: a user chooses, per type, whether they are notified and through which channels. Bundles
extend it in two places, plus an optional frontend renderer:

| Extension point | Interface | Tag |
|---|---|---|
| Notification type | `NotificationTypeDescriptorInterface` | `pimcore.studio_backend.notification_type` |
| Delivery channel | `ChannelInterface` | `pimcore.studio_backend.notification_channel` |

Both are auto-discovered — implement the interface, register the service, done. A built-in catch-all
type (`info`) already gives every untyped notification pop-up control, so you only add a type when you
want it to appear as its own subscribable row.

## How It Works

A producer builds a `DispatchableNotification` and hands it to `NotificationDispatcherInterface`. The
dispatcher resolves the recipient's subscription for that type, writes the bell entry, and fans the
delivery out to each subscribed channel. The in-app pop-up is not a channel — it is a preference read
when the notification is published over Mercure.

```php
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\DispatchableNotification;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\NotificationDispatcherInterface;

$this->dispatcher->dispatch(new DispatchableNotification(
    typeId: 'acme_crm.deal_won',
    recipientIds: [42],
    title: 'You won the ACME deal',
    message: 'The ACME renewal moved to Won.',
    senderId: 7,                 // optional
    linkedElement: $dataObject,  // optional; drives the bell attachment
    payload: ['dealId' => 1234], // optional; handed to the frontend renderer
));
```

## Notification Types

A **descriptor** describes one type: its id, labels, group and defaults. Extend
`AbstractNotificationTypeDescriptor` for sensible defaults and override what you need.

```php
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\AbstractNotificationTypeDescriptor;

final class DealWonDescriptor extends AbstractNotificationTypeDescriptor
{
    public function getTypeId(): string         { return 'acme_crm.deal_won'; }
    public function getTranslationKey(): string { return 'acme_crm.notification.deal_won.label'; }
    public function getDescriptionKey(): string { return 'acme_crm.notification.deal_won.description'; }
    public function getGroup(): string          { return 'acme_crm'; }
    public function getSortOrder(): int         { return 10; }

    // Opt in to transport channels (email, …). Defaults to false — see "Delivery Channels".
    public function allowsExternalDelivery(): bool { return true; }
}
```

| Method | Purpose |
|---|---|
| `getTypeId()` | Stable id, persisted in `notifications.type`. **Max 20 characters** (see below). |
| `getTranslationKey()` / `getDescriptionKey()` | Studio translation keys for the preferences row. |
| `getGroup()` | Groups related types on the preferences screen. |
| `getSortOrder()` | Order within the group. |
| `allowsExternalDelivery()` | Whether the type may leave the app (email, chat). `false` ⇒ pop-up only. |
| `getDefaultChannels()` | Channels pre-enabled for a new user (e.g. `['popup']`). |
| `isSubscribedByDefault()` | Whether a new user is subscribed. |
| `isSubscriptionLocked()` | Whether the user may unsubscribe. |

:::warning
`notifications.type` is `VARCHAR(20)`, and MySQL truncates silently outside strict mode, so **type ids
must be at most 20 characters** — the registry rejects longer ones at container build. Use a short
vendor prefix (`acme_crm.deal_won` is 17; `acme_crm.deal_won_late` is 22 and will not build). Ids are
persisted, so renaming later is a breaking change.
:::

## Delivery Channels

A **channel** delivers a notification outside the bell. Implement `ChannelInterface`:

```php
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\ChannelInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\User\UserInterface;

final class SlackChannel implements ChannelInterface
{
    public function getName(): string   { return 'slack'; }   // 'popup' is reserved
    public function getSortOrder(): int { return 200; }        // column order on the preferences screen

    public function send(Notification $notification, UserInterface $recipient): void
    {
        // Must not block on the network — queue it (dispatch a Messenger message and deliver from
        // the handler) so a slow endpoint never slows the action that produced the notification.
    }
}
```

- **Channels register only when a type can use them.** A channel is offered to a type only if that
  type's `allowsExternalDelivery()` is `true`. If no registered type allows external delivery, the
  transport channels are dropped entirely — a core-only install ships none, and installing a bundle
  with an externally-deliverable type brings them back automatically.
- The name **`popup`** is reserved for the in-app pop-up preference and cannot be used by a channel.
- A channel becomes available to *every* externally-deliverable type — types never enumerate channels,
  so a new channel lights up existing types with no change to them. It defaults **off** per user, so
  installing a bundle never silently starts emailing people.

## Configuration

```yaml
pimcore_studio_backend:
    notifications:
        channels:
            email:
                enabled: true   # disabling removes the channel from the preferences screen entirely
        email:
            # Brand the notification email by pointing at your own Twig template, or override the
            # default in place at
            # templates/bundles/PimcoreStudioBackendBundle/notification/email.html.twig.
            # The template receives: title, message, link, name, locale.
            template: '@PimcoreStudioBackend/notification/email.html.twig'
```

## Frontend Rendering

By default a typed notification renders as its title and message in the bell. To render it richer — an
excerpt, an action, a deep link — register a renderer on the frontend `DynamicTypeNotificationRegistry`
in **pimcore/studio-ui-bundle** (see its notification module). The `payload` you pass to
`DispatchableNotification` is what that renderer receives.
