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
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundByPathException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
trait ElementProviderTrait
{
    /**
     * @throws ElementNotFoundException
     */
    private function getElement(
        ServiceResolverInterface $serviceResolver,
        string $type,
        int $id
    ): ElementInterface {
        $element = $serviceResolver->getElementById($type, $id);
        if ($element === null) {
            throw new ElementNotFoundException($id);
        }

        return $element;
    }

    /**
     * @throws ElementNotFoundByPathException
     */
    private function getElementByPath(
        ServiceResolverInterface $serviceResolver,
        string $type,
        string $path
    ): ElementInterface {
        $element = $serviceResolver->getElementByPath($type, $path);
        if ($element === null) {
            throw new ElementNotFoundByPathException($path);
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
}
