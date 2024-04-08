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

final class AssetParentIdFilter
{
    use AssetQueryContextTrait;

    private const PARENT_ID_QUERY_PARAM = 'parentId';

    public function __construct(AssetQueryProviderInterface $assetQueryProvider)
    {
        $this->assetQueryProvider = $assetQueryProvider;
    }

    public function apply(Request $request, bool $normalization, array $attributes, array &$context): void
    {
        $parentId = $request->query->get(self::PARENT_ID_QUERY_PARAM);

        if (!$parentId) {
            return;
        }

        $assetQuery = $this->getAssetQuery($context)->filterParentId((int)$parentId);
        $this->setAssetQuery($context, $assetQuery);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::PARENT_ID_QUERY_PARAM => [
                'property' => Asset::class,
                'type' => 'int',
                'required' => false,
                'is_collection' => false,
                'description' => 'Filter assets by parent id.',
                'openapi' => [
                    'description' => 'Filter assets by parent id.',
                ],
            ],
        ];
    }
}
