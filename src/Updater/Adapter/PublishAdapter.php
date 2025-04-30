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

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorAdapter::ADAPTER_TAG)]
final readonly class PublishAdapter implements UpdateAdapterInterface
{
    private const string INDEX_KEY = 'published';

    public function __construct(
        private SecurityServiceInterface $securityService
    ) {
    }

    /**
     * @throws ElementSavingFailedException|ForbiddenException
     */
    public function update(ElementInterface $element, array $data): void
    {
        if (!array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        if (!$element instanceof Concrete && !$element instanceof Document) {
            return;
        }

        $user = $this->securityService->getCurrentUser();
        match ($data[$this->getIndexKey()]) {
            true => $this->publishElement($element, $user),
            false => $this->unpublishElement($element, $user),
            default => throw new ElementSavingFailedException(
                $element->getId(),
                sprintf(
                    'Invalid value (%s) provided for %s',
                    $data[$this->getIndexKey()],
                    $this->getIndexKey()
                )
            )
        };
    }

    public function getIndexKey(): string
    {
        return self::INDEX_KEY;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_DOCUMENT,
            ElementTypes::TYPE_OBJECT,
        ];
    }

    /**
     * @throws ForbiddenException
     */
    private function publishElement(Concrete|Document $element, UserInterface $user): void
    {
        $this->securityService->hasElementPermission($element, $user, ElementPermissions::PUBLISH_PERMISSION);
        $element->deleteAutoSaveVersions($user->getId());
        $element->setPublished(true);
    }

    /**
     * @throws ForbiddenException
     */
    private function unpublishElement(Concrete|Document $element, UserInterface $user): void
    {
        $this->securityService->hasElementPermission(
            $element,
            $user,
            ElementPermissions::UNPUBLISH_PERMISSION
        );
        $element->setOmitMandatoryCheck(true);
        $element->setPublished(false);
    }
}
