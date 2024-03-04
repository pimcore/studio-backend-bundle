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

final class AssetExcludeFolderFilter implements FilterInterface
{
    use AssetQueryContextTrait;

    private const FOLDER_FILTER_QUERY_PARAM = 'excludeFolders';

    public function __construct(AssetQueryProviderInterface $assetQueryProvider)
    {
        $this->assetQueryProvider = $assetQueryProvider;
    }

    public function apply(Request $request, bool $normalization, array $attributes, array &$context): void
    {
        $excludeFolders = $request->query->get(self::FOLDER_FILTER_QUERY_PARAM);

        if ($excludeFolders !== 'true') {
            return;
        }

        $assetQuery = $this->getAssetQuery($context)->excludeFolders();
        $this->setAssetQuery($context, $assetQuery);
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
