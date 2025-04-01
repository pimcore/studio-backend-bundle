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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class ClassDefinitionService implements ClassDefinitionServiceInterface
{
    public function __construct(
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredLayoutDefinitions(string $classId, int $folderId, ?UserInterface $user = null): ?Layout
    {
        $classDefinition = $this->getClassDefinition($classId);

        /** @var User $user
         *  Because Core needs a User
         * */
        $filteredDefinitions = $this->dataObjectServiceResolver->getCustomLayoutDefinitionForGridColumnConfig(
            $classDefinition,
            $folderId,
            $user
        );

        if (!isset($filteredDefinitions['layoutDefinition'])) {
            return null;
        }

        /** @var Layout $layoutDefinitions */
        $layoutDefinitions = $filteredDefinitions['layoutDefinition'];

        $this->dataObjectServiceResolver->enrichLayoutDefinition(
            $layoutDefinitions,
            user: $user
        );

        return $layoutDefinitions;
    }

    public function getFilteredFieldDefinitions(
        string $classId,
        int $folderId,
        ?UserInterface $user = null
    ): array {
        $classDefinition = $this->getClassDefinition($classId);

        /** @var User $user
         *  Because Core needs a User
         * */
        $filteredDefinitions = $this->dataObjectServiceResolver->getCustomLayoutDefinitionForGridColumnConfig(
            $classDefinition,
            $folderId,
            $user
        );

        return $filteredDefinitions['fieldDefinition'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getClassDefinition(string $classId): ClassDefinition
    {
        try {
            $classDefinition = $this->classDefinitionResolver->getById($classId);
        } catch (Exception) {
            $classDefinition = null;
        }

        if (!$classDefinition) {
            throw new NotFoundException('Class definition', $classId);
        }

        return $classDefinition;
    }
}
