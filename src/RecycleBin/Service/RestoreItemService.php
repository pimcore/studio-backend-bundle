<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\RecycleBin\ItemResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Hydrator\RelocatedItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Recyclebin\Item;
use Pimcore\Model\UserInterface;
use function array_map;
use function str_contains;

/**
 * @internal
 */
final readonly class RestoreItemService implements RestoreItemServiceInterface
{
    private const array MISSING_PARENT_MESSAGES = [
        'ParentID is mandatory and can´t be null',
        'ParentID not found.',
    ];

    public function __construct(
        private ItemResolverInterface $itemResolver,
        private RelocatedItemHydratorInterface $relocatedItemHydrator,
        private RestoreContextInterface $restoreContext,
        private SecurityServiceInterface $securityService,
        private SynchronousProcessingServiceInterface $synchronousProcessing,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function restoreItem(int $id, ?UserInterface $user = null): array
    {
        $syncProcessingEnabled = $this->synchronousProcessing->isEnabled();
        $item = $this->itemResolver->getById($id);
        if (!$item instanceof Item) {
            throw new NotFoundException('recycle bin item', $id);
        }

        $user ??= $this->securityService->getCurrentUser();

        try {
            $this->synchronousProcessing->enable();
            $this->restoreContext->start($user);
            $item->restore();
            $relocatedElements = $this->restoreContext->getRelocatedElements();
        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage(), $this->getRestoreErrorKey($e)->value, $e);
        } finally {
            $this->restoreContext->clear();
            $syncProcessingEnabled ? $this->synchronousProcessing->enable() :
            $this->synchronousProcessing->disable();
        }

        return array_map(
            fn (ElementInterface $element): RelocatedItem => $this->relocatedItemHydrator->hydrate($element),
            $relocatedElements
        );
    }

    private function getRestoreErrorKey(Exception $exception): HttpResponseErrorKeys
    {
        $missingParentKey = HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT;
        if ($exception instanceof EnvironmentException && $exception->getErrorKey() === $missingParentKey->value) {
            return $missingParentKey;
        }

        foreach (self::MISSING_PARENT_MESSAGES as $message) {
            if (str_contains($exception->getMessage(), $message)) {
                return $missingParentKey;
            }
        }

        return HttpResponseErrorKeys::RECYCLE_BIN_RESTORE;
    }
}
