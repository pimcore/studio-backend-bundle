<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel;

use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\Messenger\SendNotificationEmailMessage;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function str_starts_with;

/**
 * Delivers a notification as an email: send() only resolves address, language and deep link,
 * then queues a fully resolved {@see SendNotificationEmailMessage} — the blocking network send
 * happens on the pimcore_core worker. The email mirrors the bell entry (title, message, link)
 * and renders nothing from the payload.
 *
 * @internal
 */
final readonly class EmailChannel implements ChannelInterface
{
    use ElementProviderTrait;

    private const string CHANNEL_NAME = 'email';

    private const int SORT_ORDER = 100;

    private const string NO_ADDRESS_KEY = 'notifications.channel.email.no-address';

    /**
     * @param string $studioPath the Studio UI base path, wired from pimcore_studio_ui.url_path so a
     *                          customised install still gets working links; the default is that
     *                          parameter's own default for a studio-ui-less setup.
     */
    public function __construct(
        private MessageBusInterface $messageBus,
        private ToolResolverInterface $toolResolver,
        private LoggerInterface $logger,
        private string $studioPath = '/pimcore-studio',
    ) {
    }

    public function getName(): string
    {
        return self::CHANNEL_NAME;
    }

    public function getSortOrder(): int
    {
        return self::SORT_ORDER;
    }

    public function unavailableReasonFor(UserInterface $recipient): ?string
    {
        $address = $recipient->getEmail();

        return $address === null || $address === '' ? self::NO_ADDRESS_KEY : null;
    }

    public function send(Notification $notification, UserInterface $recipient): void
    {
        $address = $recipient->getEmail();
        if ($address === null || $address === '') {
            // logged: the user switched this channel on, so silence would read as a broken channel
            $this->logger->info(
                sprintf(
                    'Notification email skipped: user %d has the email channel enabled but no ' .
                    'email address set.',
                    $recipient->getId()
                )
            );

            return;
        }

        $this->messageBus->dispatch(
            new SendNotificationEmailMessage(
                to: $address,
                // a user without first/last name would otherwise be greeted "Hi ,"
                toName: $recipient->getFullName() ?: (string) $recipient->getUsername(),
                locale: $recipient->getLanguage(),
                subject: $notification->getTitle() ?? '',
                title: $notification->getTitle() ?? '',
                message: $notification->getMessage() ?? '',
                link: $this->buildDeepLink($notification),
            )
        );
    }

    private function buildDeepLink(Notification $notification): string
    {
        $host = $this->resolveHostUrl();

        // A producer may supply a better destination as an app-relative path in the payload —
        // the one navigation hint read from it. Only host-relative paths are honoured, so a
        // payload can never turn the button into an off-site or "javascript:" link.
        $producerLink = $this->producerDeepLink($notification);
        if ($producerLink !== null) {
            return $host . $producerLink;
        }

        $base = $host . $this->studioPath;

        $element = $notification->getLinkedElement();
        $type = $element instanceof ElementInterface ? $this->studioElementType($element) : null;

        if ($element === null || $type === null) {
            return $base . '/';
        }

        return sprintf('%s/%s/%d', $base, $type, $element->getId());
    }

    private function producerDeepLink(Notification $notification): ?string
    {
        $payload = json_decode($notification->getPayload() ?? '[]', true);
        $link = is_array($payload) ? ($payload['deepLink'] ?? null) : null;

        if (!is_string($link) || !str_starts_with($link, '/')) {
            return null;
        }

        // "//host" would be protocol-relative when the host prefix is legitimately empty
        return str_starts_with($link, '//') ? null : $link;
    }

    /**
     * The Studio route segment for the element, via the bundle's canonical element→type mapping.
     * A type that mapping doesn't cover yields no segment, so the link falls back to the base URL.
     */
    private function studioElementType(ElementInterface $element): ?string
    {
        try {
            return $this->getElementType($element);
        } catch (InvalidElementTypeException) {
            return null;
        }
    }

    /**
     * The current request's host, falling back to pimcore.general.domain and then to '' — via the
     * same Tool::getHostUrl() the core workflow-notification mail uses. An empty result means the
     * link cannot be made absolute (no request, no domain); logged rather than left silent.
     */
    private function resolveHostUrl(): string
    {
        $hostUrl = $this->toolResolver->getHostUrl();
        if ($hostUrl === '') {
            $this->logger->warning(
                'Notification email links cannot be made absolute: no request is available and ' .
                'pimcore.general.domain is not set, so the link in the email will not resolve.'
            );
        }

        return $hostUrl;
    }
}
