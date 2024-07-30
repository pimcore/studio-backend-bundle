<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Email\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\Helper\MailResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Mail;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Email;
use Pimcore\Model\UserInterface;
use Symfony\Component\Mime\Address;
use function sprintf;

/**
 * @internal
 */
final readonly class MailService implements MailServiceInterface
{
    private const ADD_PREFIX = 'add';

    private const GET_PREFIX = 'get';

    private const ADDRESS_EMAIL = 'email';

    private const ADDRESS_NAME = 'name';

    public function __construct(
        private AssetServiceInterface $assetService,
        private MailResolverInterface $mailResolver
    ) {
    }

    /**
     * @throws EnvironmentException
     */
    public function setMailFromAddress(string $from, Mail $mail): void
    {
        $fromAddresses = $this->mailResolver->parseEmailAddressField($from);
        if (empty($fromAddresses)) {
            throw new EnvironmentException('Invalid from email address');
        }

        $mail->from($this->getAddressObject($fromAddresses[0]));
    }

    /**
     * @throws EnvironmentException
     */
    public function addMailAddress(?string $address, string $addressType, Mail $mail): void
    {
        if ($address === null) {
            return;
        }

        $parsedAddresses = $this->mailResolver->parseEmailAddressField($address);

        foreach ($parsedAddresses as $parsedAddress) {
            $method = ucfirst($addressType);
            $getter = self::GET_PREFIX . $method;
            $setter = self::ADD_PREFIX . $method;
            if (!method_exists($mail, $getter) || !method_exists($mail, $setter)) {
                throw new EnvironmentException(sprintf('Invalid address type: %s', $addressType));
            }
            $newAddress = $this->getAddressObject($parsedAddress);
            $currentAddresses = $mail->$getter();
            if (empty($currentAddresses)) {
                $mail->$setter($newAddress);
                return;
            }

            foreach ($mail->$getter() as $currentAddress) {
                if ($currentAddress->getAddress() !== $newAddress->getAddress() ||
                    $currentAddress->getName() !== $newAddress->getName()
                ) {
                    $mail->$setter($newAddress);
                }
            }
        }
    }

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function setMailDocumentContent(Document $document, Mail $mail): void
    {
        if (!$document instanceof Email) {
            throw new InvalidElementTypeException($document->getType());
        }

        try {
            $mail->setDocument($document);
        } catch (Exception $exception) {
            throw new EnvironmentException(
                sprintf(
                    'Failed to set email document: %s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @throws AccessDeniedException
     * @throws NotFoundException
     */
    public function addMailAttachment(?int $attachmentId, Mail $mail, UserInterface $user): void
    {
        if ($attachmentId === null) {
            return;
        }

        $attachment = $this->assetService->getAssetElement($user, $attachmentId);
        $mail->attach($attachment->getData(), $attachment->getFilename(), $attachment->getMimeType());
    }

    private function getAddressObject(array $address): Address
    {
        return new Address($address[self::ADDRESS_EMAIL], trim($address[self::ADDRESS_NAME], '"\''));
    }
}
