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

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Element\DuplicateFullPathException;

/**
 * @internal
 */
final class UpdateService implements UpdateServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private readonly AdapterLoaderInterface $adapterLoader,
        private readonly ServiceResolver $serviceResolver
    )
    {
    }

    /**
     * @throws ElementSavingFailedException|ElementNotFoundException
     */
    public function update(string $elementType, int $id, array $data): void
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        foreach ($this->adapterLoader->loadAdapters($elementType) as $adapter) {
            if (array_key_exists($adapter->getDataIndex(), $data)) {
                $adapter->update($element, $data);
            }
        }

        try {
            $element->save();
        } catch (DuplicateFullPathException $e) {
            throw new ElementSavingFailedException($id, $e->getMessage());
        }
    }
}
