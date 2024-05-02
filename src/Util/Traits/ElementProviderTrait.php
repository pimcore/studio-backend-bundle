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

use InvalidArgumentException;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
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
     * @throws InvalidArgumentException
     */
    private function getElement(
        ServiceResolverInterface $serviceResolver,
        string $type,
        int $id,
        ?UserInterface $user = null,
    ): ElementInterface {
        $element = $serviceResolver->getElementById($type, $id);
        if ($element === null) {
            throw new ElementNotFoundException($id);
        }

        return $this->getLatestVersionForUser($element, $user);
    }

    private function getLatestVersionForUser(
        Asset|Document|Concrete $element,
        ?UserInterface $user
    ): ElementInterface
    {
        // check for latest version
        $version = $element->getLatestVersion($user?->getId());

        if ($version) {
            return $version->getData();
        }

        return $element;
    }

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
