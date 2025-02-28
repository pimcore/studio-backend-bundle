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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;

/**
 * @internal
 */
final readonly class UpdateService implements UpdateServiceInterface
{
    use ElementProviderTrait;
    use ValidateObjectDataTrait;

    public function __construct(
        private AdapterLoaderInterface $adapterLoader,
        private DataServiceInterface $objectDataService,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private ElementSaveServiceInterface $elementSaveService,
    ) {
    }

    /**
     * @throws ElementSavingFailedException|FieldValidationFailedException|NotFoundException
     */
    public function update(string $elementType, int $id, array $data): void
    {
        $user = $this->securityService->getCurrentUser();
        $element = $this->getElement($this->serviceResolver, $elementType, $id);
        $task = $data[ElementSaveServiceInterface::INDEX_TASK] ?? null;
        if (isset($data[self::USE_DRAFT_DATA_KEY]) && $data[self::USE_DRAFT_DATA_KEY] === true) {
            $draftElement = $this->getDraftElement($element);
            $considerDraftData = $element !== $draftElement;

            if ($considerDraftData && $draftElement instanceof Concrete && $element instanceof Concrete) {
                $this->objectDataService->handleDraftData($draftElement, $element, $task);
                $element = $draftElement;
            }
        }

        if (isset($data[self::EDITABLE_DATA_KEY]) && $element instanceof Concrete) {
            $this->objectDataService->updateEditableData($element, $data[self::EDITABLE_DATA_KEY], $user);
            unset($data[self::EDITABLE_DATA_KEY]);
        }

        foreach ($this->adapterLoader->loadAdapters($elementType) as $adapter) {
            $adapter->update($element, $data);
        }

        try {
            $this->elementSaveService->save($element, $user, $task);
        } catch (ValidationException $e) {
            throw new FieldValidationFailedException($e->getMessage(), previous: $e);
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage());
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
