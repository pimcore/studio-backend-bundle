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
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\Messenger\SendNotificationEmailMessage;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Notification;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function str_starts_with;

/**
 * Delivers a notification as an email through the existing Pimcore mailer.
 *
 * send() runs inside the request that produced the notification, so it only does the cheap work
 * — resolve the recipient's address, language and the absolute deep link — and hands a fully
 * resolved {@see SendNotificationEmailMessage} to the pimcore_core transport. The blocking
 * network send happens later in the worker, so a slow or unreachable mail server never delays
 * the comment, assignment or invite that triggered the notification.
 *
 * The email deliberately mirrors the bell entry: the notification's own title and message plus a
 * deep link, and nothing from the payload. If it is not in the bell, it is not in the inbox.
 *
 * @internal
 */
final readonly class EmailChannel implements ChannelInterface
{
    private const string CHANNEL_NAME = 'email';

    private const int SORT_ORDER = 100;

    /**
     * The Studio UI serves element deep links under this prefix; it matches the default of the
     * UI bundle's pimcore_studio_ui.url_path. Kept as a constant rather than coupling the backend
     * to the UI bundle's parameter — a customised url_path would only affect the link, never
     * whether the mail is sent.
     */
    private const string STUDIO_PATH = '/pimcore-studio';

    public function __construct(
        private MessageBusInterface $messageBus,
        private RequestStack $requestStack,
        private ToolResolverInterface $toolResolver,
        private LoggerInterface $logger,
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

    public function send(Notification $notification, UserInterface $recipient): void
    {
        $address = $recipient->getEmail();
        if ($address === null || $address === '') {
            return;
        }

        $this->messageBus->dispatch(
            new SendNotificationEmailMessage(
                to: $address,
                // A Pimcore user need not have a first or last name, and the greeting reads
                // "Hi ," when it does not.
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

        // A producer that knows a better destination than the linked element — Collab pointing at
        // a task or discussion in its overview, for instance — supplies an app-relative path in the
        // payload. It is the one navigation hint the email reads from the payload; no payload
        // content is ever rendered. Only host-relative paths are honoured, so a payload can never
        // turn the button into an off-site or "javascript:" link.
        $producerLink = $this->producerDeepLink($notification);
        if ($producerLink !== null) {
            return $host . $producerLink;
        }

        $base = $host . self::STUDIO_PATH;

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

        // "//host" is protocol-relative once the host prefix is empty, which resolveHostUrl()
        // legitimately is in a worker with no configured domain.
        return str_starts_with($link, '//') ? null : $link;
    }

    /**
     * The Studio element route segment ('asset', 'document', 'data-object'), or null for an
     * element type that has no such route.
     */
    private function studioElementType(ElementInterface $element): ?string
    {
        return match (true) {
            $element instanceof Asset => ElementTypes::TYPE_ASSET,
            $element instanceof Document => ElementTypes::TYPE_DOCUMENT,
            $element instanceof DataObject => ElementTypes::TYPE_DATA_OBJECT,
            default => null,
        };
    }

    /**
     * The request host when there is a request, the configured domain otherwise — a notification
     * dispatched from a command or a consumer has neither a request nor, unless the installation
     * sets pimcore.general.domain, a host to fall back on.
     *
     * That case yields a host-relative link, which in an email is simply a dead button. There is
     * nothing better to emit, so it is logged rather than left to be discovered by the recipient.
     */
    private function resolveHostUrl(): string
    {
        $request = $this->requestStack->getMainRequest();
        if ($request !== null) {
            return $request->getSchemeAndHttpHost();
        }

        $hostname = $this->toolResolver->getHostname();
        if ($hostname === null || $hostname === '') {
            $this->logger->warning(
                'Notification email links cannot be made absolute: no request is available and ' .
                'pimcore.general.domain is not set, so the link in the email will not resolve.'
            );

            return '';
        }

        return $this->toolResolver->getRequestScheme() . '://' . $hostname;
    }
}
