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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\RelationData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ManyToManyRelationAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use ElementProviderTrait;

    public function __construct(
        private RelationDataServiceInterface $relationDataService,
        private ServiceResolverInterface $serviceResolver
    ) {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?array {
        $relationData = $this->relationDataService->getSetterData(
            $element,
            $fieldDefinition,
            $this,
            $isPatch,
            $data[$key],
            $contextData
        );
        if ($relationData === null) {
            return null;
        }

        return $this->getRelationElements($relationData);
    }

    /**
     * @return RelationData[]|null
     */
    public function normalize(
        mixed $value,
        Data $fieldDefinition
    ): ?array {
        if (!is_array($value)) {
            return null;
        }

        return $this->relationDataService->getRelationElementsData($value);
    }

    private function getRelationElements(array $relationData): array
    {
        $relations = [];
        foreach ($relationData as $relation) {
            try {
                $element = $this->getElement($this->serviceResolver, $relation['type'], $relation['id']);
            } catch (NotFoundException) {
                continue;
            }

            $relations[] = $element;
        }

        return $relations;
    }
}
