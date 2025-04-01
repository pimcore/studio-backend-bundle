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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Data\Adapter;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataDenormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Data\MetaDataAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\AdapterLoader;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(AdapterLoader::METADATA_ADAPTER_TAG->value)]
final readonly class ManyToOneRelationAdapter implements
    MetaDataAdapterInterface,
    DataNormalizerInterface,
    DataDenormalizerInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver
    ) {
    }

    public function normalize(mixed $value, string $type): ?array
    {
        if (!$value instanceof ElementInterface) {
            return null;
        }

        return [
            'id' => $value->getId(),
            'type' => $this->getElementType($value, true),
            'fullPath' => $value->getRealFullPath(),
            'subtype' => $value->getType(),
            'isPublished' => ($value instanceof Concrete || $value instanceof Document) ?
                $value->isPublished() :
                null,
        ];
    }

    public function denormalize(
        array $customMetadata,
        UserInterface $user,
        array $existingMetadata = [],
        bool $isPatch = false
    ): ?ElementInterface {
        $value = $customMetadata['data'] ?? null;
        if (!is_array($value) || empty($value['id'])) {
            return null;
        }

        try {
            return $this->getElement($this->serviceResolver, $value['type'], $value['id']);
        } catch (NotFoundException) {
            return null;
        }
    }
}
