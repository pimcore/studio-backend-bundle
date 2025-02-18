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
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_key_exists;

/**
 * @internal
 */
final readonly class UpdateService implements UpdateServiceInterface
{
    use ElementProviderTrait;
    use ValidateObjectDataTrait;

    public function __construct(
        private AdapterLoaderInterface $adapterLoader,
        private DataAdapterServiceInterface $dataAdapterService,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private ElementSaveServiceInterface $elementSaveService,
    ) {
    }

    /**
     * @throws ElementSavingFailedException|NotFoundException
     */
    public function update(string $elementType, int $id, array $data): void
    {
        $user = $this->securityService->getCurrentUser();
        $element = $this->getElement($this->serviceResolver, $elementType, $id);
        if (isset($data[self::USE_DRAFT_DATA_KEY]) && $data[self::USE_DRAFT_DATA_KEY] === true) {
            $element = $this->getDraftElement($element);
        }

        if (isset($data[self::EDITABLE_DATA_KEY]) && $element instanceof Concrete) {
            $this->updateEditableData($element, $data[self::EDITABLE_DATA_KEY], $user);
            unset($data[self::EDITABLE_DATA_KEY]);
        }

        foreach ($this->adapterLoader->loadAdapters($elementType) as $adapter) {
            $adapter->update($element, $data);
        }

        try {
            $this->elementSaveService->save(
                $element,
                $user,
                $data[ElementSaveServiceInterface::INDEX_TASK] ?? null
            );
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage());
        }
    }

    /**
     * @throws ElementSavingFailedException
     */
    public function updateEditableData(Concrete $element, array $editableData, UserInterface $user): void
    {
        try {
            $class = $element->getClass();
            foreach ($editableData as $key => $value) {
                $fieldDefinition = $class->getFieldDefinition($key);
                if ($fieldDefinition === null || !array_key_exists($key, $editableData)) {
                    continue;
                }

                $adapter = $this->dataAdapterService->tryDataAdapter($fieldDefinition->getFieldtype());
                if ($adapter === null) {
                    continue;
                }

                $value = $adapter->getDataForSetter($element, $fieldDefinition, $key, $editableData, $user);
                if (!$this->validateEncryptedField($fieldDefinition, $value)) {
                    continue;
                }

                $element->setValue($key, $value);
            }

        } catch (Exception $e) {
            throw new ElementSavingFailedException($element->getId(), $e->getMessage());
        }
    }

    private function getDraftElement(ElementInterface $element): ElementInterface
    {
        if (!$element instanceof Concrete && !$element instanceof Document) {
            return $element;
        }

        $version = $this->getLatestVersionForUser($element, $this->securityService->getCurrentUser());

        return $this->getVersionData($element, $version);
    }
}
