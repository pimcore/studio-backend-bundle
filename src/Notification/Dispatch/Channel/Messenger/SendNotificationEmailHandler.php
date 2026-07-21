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

namespace Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\Messenger;

use Pimcore\Mail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Twig\Environment;

/**
 * Renders and sends one notification email. Runs on the pimcore_core worker, so the blocking
 * network send is off the request path; a failure bubbles up and Messenger retries it per that
 * transport's policy, while Pimcore logs the attempt either way.
 *
 * @internal
 */
#[AsMessageHandler]
final readonly class SendNotificationEmailHandler
{
    /**
     * @param string $template the configured email template — the shipped default, an app-level
     *                         override at templates/bundles/PimcoreStudioBackendBundle/…, or a
     *                         custom template named in pimcore_studio_backend.notifications.email
     */
    public function __construct(
        private Environment $twig,
        private MailerInterface $mailer,
        private string $fromEmail,
        private string $template,
    ) {
    }

    public function __invoke(SendNotificationEmailMessage $message): void
    {
        $body = $this->twig->render($this->template, [
            'title' => $message->getTitle(),
            'message' => $message->getMessage(),
            'link' => $message->getLink(),
            'name' => $message->getToName(),
            'locale' => $message->getLocale(),
        ]);

        $mail = new Mail();
        $mail->from(new Address($this->fromEmail));
        $mail->to(new Address($message->getTo(), $message->getToName()));
        $mail->subject($message->getSubject());
        $mail->html($body);

        // The body and subject are already fully rendered above, so Pimcore's document/
        // placeholder pass is skipped — a literal "%" in a title must not be read as a
        // placeholder. Logging, the block-list and the debug-mode redirect still apply.
        $mail->sendWithoutRendering($this->mailer);
    }
}
