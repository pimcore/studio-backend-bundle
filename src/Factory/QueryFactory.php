<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Factory;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DocumentQueryProviderInterface;
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
        private DataObjectQueryProviderInterface $dataObjectQueryProvider,
        private DocumentQueryProviderInterface $documentQueryProvider,
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
            ElementTypes::TYPE_DOCUMENT => $this->documentQueryProvider->createDocumentQuery(),
            default => throw new InvalidQueryTypeException(
                HttpResponseCodes::BAD_REQUEST->value,
                "Unknown query type: $type"
            )
        };
    }
}
