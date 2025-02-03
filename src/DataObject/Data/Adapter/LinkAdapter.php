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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\UserInterface;
use Pimcore\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class LinkAdapter implements SetterDataInterface, DataNormalizerInterface
{
    use ElementProviderTrait;

    public function __construct(private ServiceResolverInterface $serviceResolver)
    {
    }

    /**
     * @throws Exception
     */
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?Link {

        $link = new Link();
        $link->setValues($data[$key]);

        if ($link->isEmpty()) {
            return null;
        }

        return $link;
    }

    public function normalize(mixed $value, Data $fieldDefinition): mixed
    {
        if (!$value instanceof Link || !$fieldDefinition instanceof NormalizerInterface) {
            return null;
        }

        $data = $fieldDefinition->normalize($value);

        $data['fullPath'] = $this->getFullPath($value);

        return $data;
    }

    private function getFullPath(Link $link): ?string
    {

        if ($link->getDirect() !== null) {
            return $link->getDirect();
        }

        if (!$link->getInternal() || !$link->getInternalType()) {
            return null;
        }

        try {
            $element = $this->getElement(
                $this->serviceResolver,
                $link->getInternalType(),
                $link->getInternal()
            );

            return $element->getRealFullPath();
        } catch (NotFoundException) {
            return null;
        }
    }
}
