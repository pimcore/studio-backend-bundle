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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Voter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\RequestTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use function array_key_exists;

/**
 * @internal
 */
final class ElementTypePermissionVoter extends Voter
{
    use RequestTrait;

    private const TYPE_TO_PERMISSION = [
        ElementTypes::TYPE_ASSET => UserPermissions::ASSETS->value,
        ElementTypes::TYPE_DOCUMENT => UserPermissions::DOCUMENTS->value,
        ElementTypes::TYPE_DATA_OBJECT => UserPermissions::DATA_OBJECTS->value,
        ElementTypes::TYPE_OBJECT => UserPermissions::DATA_OBJECTS->value,
    ];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SecurityServiceInterface $securityService

    ) {
    }

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === UserPermissions::ELEMENT_TYPE_PERMISSION->value;
    }

    /**
     * @throws AccessDeniedException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $elementType = $this->getElementTypeFromRequest();

        if (!$elementType || !array_key_exists($elementType, self::TYPE_TO_PERMISSION)) {
            return false;
        }

        return $this->securityService->getCurrentUser()->isAllowed(self::TYPE_TO_PERMISSION[$elementType]);
    }

    private function getElementTypeFromRequest(): string
    {
        $request = $this->getCurrentRequest($this->requestStack);

        return $request->attributes->getString('elementType');
    }
}
