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

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Model\Asset as CoreAsset;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Exception\NotFoundException;

final readonly class AssetService implements AssetServiceInterface
{
    public function __construct(
        private AssetResolverInterface $assetResolver,
        private SynchronousProcessingServiceInterface $synchronousProcessingService
    ) {
    }

    /**
     * @throws DuplicateFullPathException
     */
    public function processAsset(Asset $data): CoreAsset
    {
        $asset = $this->assetResolver->getById($data->getId());

        if(!$asset) {
            throw new NotFoundException('Asset not found');
        }

        $differences = $this->compare($asset, $data);
        foreach($differences as $field => $difference) {
            $setter = 'set' . $field;
            $asset->$setter($difference['new']);
        }
        $this->synchronousProcessingService->enable();
        $asset->save();

        return $asset;
    }

    private function compare(CoreAsset $current, Asset $data): array
    {
        $fieldsToCheck = $this->fieldsToCheck(get_class_methods($data));
        $differences = [];
        foreach($fieldsToCheck as $field) {
            if($current->$field() !== $data->$field()) {
                $fieldName = str_replace('get', '', $field);
                $differences[$fieldName] = [
                    'current' => $current->$field(),
                    'new' => $data->$field(),
                ];
            }
        }

        return $differences;
    }

    private function fieldsToCheck(array $methods): array
    {
        $setters = array_filter($methods, static fn ($method) => str_starts_with($method, 'set'));

        return array_map(static fn ($setter) => str_replace('set', 'get', $setter), $setters);
    }
}
