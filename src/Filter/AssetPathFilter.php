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
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryContextTrait;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final class AssetPathFilter implements FilterInterface
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

        $includeDescendants = $this->getBooleanValueFromQuery(
            $request,
            self::AP_INCLUDE_DESCENDANTS_PARAM,
            false
        );

        $includeParent = $this->getBooleanValueFromQuery(
            $request,
            self::AP_INCLUDE_PARENT_PARAM,
            false
        );

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

    private function getBooleanValueFromQuery(Request $request, string $queryName, bool $defaultValue): bool
    {
        return filter_var(
            $request->query->get($queryName, $defaultValue),
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
