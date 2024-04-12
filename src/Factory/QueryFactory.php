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

namespace Pimcore\Bundle\StudioApiBundle\Factory;

use Pimcore\Bundle\StudioApiBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\QueryInterface;

final readonly class QueryFactory implements QueryFactoryInterface
{
    public function __construct(private AssetQueryProviderInterface $assetQueryProvider)
    {

    }

    /**
     * @throws InvalidQueryTypeException
     */
    public function create(string $type): QueryInterface
    {
        return match($type) {
            'asset' => $this->assetQueryProvider->createAssetQuery(),
            default => throw new InvalidQueryTypeException("Unknown query type: $type")
        };
    }
}