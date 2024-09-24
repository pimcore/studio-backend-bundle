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

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\Concrete;
use function Symfony\Component\String\s;

/**
 * @internal
 */
final readonly class UpdateService implements UpdateServiceInterface
{
    use ElementProviderTrait;

    private const EDITABLE_DATA_KEY = 'editableData';

    public function __construct(
        private SynchronousProcessingServiceInterface $synchronousProcessingService,
        private SecurityServiceInterface $securityService,
        private AdapterLoaderInterface $adapterLoader,
        private DataAdapterLoaderInterface $dataAdapterLoader,
        private ServiceResolver $serviceResolver
    ) {
    }

    /**
     * @throws ElementSavingFailedException|NotFoundException
     */
    public function update(string $elementType, int $id, array $data): void
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);
        if (isset($data[self::EDITABLE_DATA_KEY]) && $element instanceof Concrete) {
            $this->updateEditableData($element, $data[self::EDITABLE_DATA_KEY]);
            unset($data[self::EDITABLE_DATA_KEY]);
        }

        foreach ($this->adapterLoader->loadAdapters($elementType) as $adapter) {
            $adapter->update($element, $data);
        }

        try {
            $this->synchronousProcessingService->enable();
            $element->setUserModification($this->securityService->getCurrentUser()->getId());
            $element->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage());
        }
    }

    /**
     * @throws ElementSavingFailedException
     */
    private function updateEditableData(Concrete $element, array $editableData): void
    {
        try {
            $class = $element->getClass();
            foreach ($editableData as $key => $value) {
                $fieldDefinition = $class->getFieldDefinition($key);
                if ($fieldDefinition === null) {
                    continue;
                }

                $adapter = $this->dataAdapterLoader->loadAdapter($fieldDefinition);
                $data = $adapter->getDataForSetter($element, $fieldDefinition, $key, $editableData);
                $element->setValue($key, $data);
            }

        } catch (Exception $e) {
            throw new ElementSavingFailedException($element->getId(), $e->getMessage());
        }
    }
}
