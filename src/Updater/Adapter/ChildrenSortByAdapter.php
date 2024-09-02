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

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class ChildrenSortByAdapter implements UpdateAdapterInterface
{
    private const INDEX_KEY = 'childrenSortBy';

    public function __construct(
        private SecurityServiceInterface $securityService
    ) {

    }

    /**
     * @throws ElementSavingFailedException
     */
    public function update(ElementInterface $element, array $data): void
    {
        if (!$element instanceof AbstractObject || !array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $user = $this->securityService->getCurrentUser();
        if (!$user->isAllowed(UserPermissions::OBJECTS_SORT_METHOD->value)) {
            throw new AccessDeniedException('You are not allowed to change the sort method');
        }

        $value = $data[$this->getIndexKey()];
        if (!in_array(
            $value,
            [AbstractObject::OBJECT_CHILDREN_SORT_BY_DEFAULT, AbstractObject::OBJECT_CHILDREN_SORT_BY_INDEX],
            true
        )) {
            throw new ElementSavingFailedException(null, sprintf('Invalid sort method "%s"', $value));
        }

        $element->setChildrenSortBy($data[$this->getIndexKey()]);
    }

    public function getIndexKey(): string
    {
        return self::INDEX_KEY;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_OBJECT,
        ];
    }
}
