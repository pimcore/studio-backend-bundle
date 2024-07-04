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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function get_class;

/**
 * @internal
 */
trait ElementProviderTrait
{
    /**
     * @throws NotFoundException
     */
    private function getElement(
        ServiceResolverInterface $serviceResolver,
        string $type,
        int $id
    ): ElementInterface {
        $element = $serviceResolver->getElementById($type, $id);
        if ($element === null) {
            throw new NotFoundException($type, $id);
        }

        return $element;
    }

    /**
     * @throws NotFoundException
     */
    private function getElementByPath(
        ServiceResolverInterface $serviceResolver,
        string $type,
        string $path
    ): ElementInterface {
        $element = $serviceResolver->getElementByPath($type, $path);
        if ($element === null) {
            throw new NotFoundException($type, $path, 'path');
        }

        return $element;
    }

    private function getLatestVersionForUser(
        ElementInterface $element,
        ?UserInterface $user
    ): ElementInterface {
        if (!$element instanceof Asset &&
            !$element instanceof Document\PageSnippet &&
            !$element instanceof Concrete
        ) {
            return $element;
        }

        // check for latest version
        $version = $element->getLatestVersion($user?->getId());
        if ($version) {
            return $version->getData();
        }

        return $element;
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function getElementClass(ElementInterface $element): string
    {
        return match (true) {
            $element instanceof Asset => Asset::class,
            $element instanceof Document => Document::class,
            $element instanceof DataObject => DataObject::class,
            default => throw new InvalidElementTypeException(get_class($element))
        };
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function getElementType(ElementInterface $element): string
    {
        return match (true) {
            $element instanceof Asset => ElementTypes::TYPE_ASSET,
            $element instanceof Document => ElementTypes::TYPE_DOCUMENT,
            $element instanceof DataObject => ElementTypes::TYPE_DATA_OBJECT,
            default => throw new InvalidElementTypeException($element->getType())
        };
    }
}
