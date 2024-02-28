<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Filter;

use ApiPlatform\Serializer\Filter\FilterInterface;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Symfony\Component\HttpFoundation\Request;

final class AssetFolderFilter implements FilterInterface
{
    public const ASSET_FOLDER_FILTER_CONTEXT = 'asset_folder_filter';
    private const FOLDER_FILTER_QUERY_PARAM = 'filterFolders';

    public function apply(Request $request, bool $normalization, array $attributes, array &$context): void
    {
        $parentId = $request->query->get(self::FOLDER_FILTER_QUERY_PARAM);

        if (!$parentId) {
            return;
        }

        $context[self::ASSET_FOLDER_FILTER_CONTEXT] = (bool)$parentId;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::FOLDER_FILTER_QUERY_PARAM => [
                'property' => Asset::class,
                'type' => 'bool',
                'required' => false,
                'is_collection' => false,
                'description' => 'Filter folders from result.',
                'openapi' => [
                    'description' => 'Filter folders from result.',
                ],
            ],
        ];
    }
}
