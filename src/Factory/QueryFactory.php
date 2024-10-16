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

namespace Pimcore\Bundle\StudioBackendBundle\Factory;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;

/**
 * @internal
 */
final readonly class QueryFactory implements QueryFactoryInterface
{
    public function __construct(
        private AssetQueryProviderInterface $assetQueryProvider,
        private DataObjectQueryProviderInterface $dataObjectQueryProvider
    ) {

    }

    /**
     * @throws InvalidQueryTypeException
     */
    public function create(string $type): QueryInterface
    {
        return match($type) {
            ElementTypes::TYPE_ASSET => $this->assetQueryProvider->createAssetQuery(),
            ElementTypes::TYPE_DATA_OBJECT => $this->dataObjectQueryProvider->createDataObjectQuery(),
            default => throw new InvalidQueryTypeException(
                HttpResponseCodes::BAD_REQUEST->value,
                "Unknown query type: $type"
            )
        };
    }
}
