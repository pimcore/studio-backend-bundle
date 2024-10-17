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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Model\UserInterface;

final readonly class ElementSearchService implements ElementSearchServiceInterface
{
    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private DocumentSearchServiceInterface $documentSearchService,

    ) {
    }

    public function getElementById(string $type, int $id, ?UserInterface $user = null): mixed
    {
        return match ($type) {
            'asset' => $this->assetSearchService->getAssetById($id, $user),
            'dataObject' => $this->dataObjectSearchService->getDataObjectById($id, $user),
            'document' => $this->documentSearchService->getDocumentById($id, $user),
            default => throw new InvalidElementTypeException($type),
        };
    }

    public function getChildrenIds(string $type, string $parentPath, ?string $sortDirection = null): array
    {
        return match ($type) {
            'asset' => $this->assetSearchService->getChildrenIds($parentPath, $sortDirection),
            'dataObject' => $this->dataObjectSearchService->getChildrenIds($parentPath, $sortDirection),
            'document' => $this->documentSearchService->getChildrenIds($parentPath, $sortDirection),
            default => throw new InvalidElementTypeException($type),
        };
    }
}
