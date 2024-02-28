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

final class AssetIdSearchFilter implements FilterInterface
{
    public const ASSET_ID_SEARCH_FILTER = 'asset_id_search_filter';
    private const ID_SEARCH_FILTER_QUERY_PARAM = 'idSearchTerm';

    public function apply(Request $request, bool $normalization, array $attributes, array &$context): void
    {
        $searchIdTerm = $request->query->get(self::ID_SEARCH_FILTER_QUERY_PARAM);

        if (!$searchIdTerm) {
            return;
        }

        $context[self::ASSET_ID_SEARCH_FILTER] = $searchIdTerm;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::ID_SEARCH_FILTER_QUERY_PARAM => [
                'property' => Asset::class,
                'type' => 'string',
                'required' => false,
                'is_collection' => false,
                'description' => 'Filters assets by matching ids. As a wildcard, you can use *.',
                'openapi' => [
                    'description' => 'Filters assets by matching ids. As a wildcard, you can use *.',
                ],
            ],
        ];
    }
}
