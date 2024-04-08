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

namespace Pimcore\Bundle\StudioApiBundle\Filter;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryContextTrait;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final class AssetPathFilter
{
    use AssetQueryContextTrait;

    private const AP_QUERY_PARAM = 'assetPath';

    private const AP_INCLUDE_PARENT_PARAM = 'assetPathIncludeParent';

    private const AP_INCLUDE_DESCENDANTS_PARAM = 'assetPathIncludeDescendants';

    public function __construct(AssetQueryProviderInterface $assetQueryProvider)
    {
        $this->assetQueryProvider = $assetQueryProvider;
    }

    public function apply(Request $request, bool $normalization, array $attributes, array &$context): void
    {
        $path = $request->query->get(self::AP_QUERY_PARAM);

        if (!$path) {
            return;
        }

        $includeDescendants = $request->query->getBoolean(self::AP_INCLUDE_DESCENDANTS_PARAM);
        $includeParent = $request->query->getBoolean(self::AP_INCLUDE_PARENT_PARAM);

        $assetQuery = $this->getAssetQuery($context)->filterPath($path, $includeDescendants, $includeParent);
        $this->setAssetQuery($context, $assetQuery);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::AP_QUERY_PARAM => [
                'property' => Asset::class,
                'type' => 'string',
                'required' => false,
                'is_collection' => false,
                'description' => 'Filter assets by path.',
                'openapi' => [
                    'description' => 'Filter assets by path.',
                ],
            ],
            self::AP_INCLUDE_PARENT_PARAM => [
                'property' => Asset::class,
                'type' => 'bool',
                'required' => false,
                'is_collection' => false,
                'description' => 'Include the parent item in the result.',
                'openapi' => [
                    'description' => 'Include the parent item in the result.',
                ],
            ],
            self::AP_INCLUDE_DESCENDANTS_PARAM => [
                'property' => Asset::class,
                'type' => 'bool',
                'required' => false,
                'is_collection' => false,
                'description' => 'Include all descendants in the result.',
                'openapi' => [
                    'description' => 'Include all descendants in the result.',
                ],
            ],
        ];
    }
}
