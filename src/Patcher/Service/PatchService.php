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

namespace Pimcore\Bundle\StudioBackendBundle\Patcher\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;

/**
 * @internal
 */
final class PatchService implements PatchServiceInterface
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
    public function patch(string $elementType, array $patchData): array
    {
       $adapters = $this->adapterLoader->loadAdapters($elementType);

        $error = [];

       foreach($patchData as $data) {
           try {
               $element  = $this->getElement($this->serviceResolver, $elementType, $data['id']);
               foreach($adapters as $adapter) {
                   $adapter->patch($element, $data);
               }

               $element->save();

           } catch (Exception $exception) {
               $error[] = [
                   'id' => $data['id'],
                   'message' => $exception->getMessage(),
               ];
           }
       }

       return $error;
    }
}
