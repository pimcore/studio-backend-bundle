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

class AssetParentIdFilter implements FilterInterface
{
    public const ASSET_PARENT_ID_FILTER_CONTEXT = 'asset_parent_id_filter';

    public function apply(Request $request, bool $normalization, array $attributes, array &$context): void
    {
        $parentId = $request->query->get('parentId');

        if (!$parentId) {
            return;
        }

        $context[self::ASSET_PARENT_ID_FILTER_CONTEXT] = $parentId;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'parentId' => [
                'property' => Asset::class,
                'type' => 'int',
                'required' => true,
                'is_collection' => false,
                'description' => 'Filters assets by parent id.',
                'openapi' => [
                    'description' => 'Filters assets by parent id.',
                ],
            ],
        ];
    }
}
