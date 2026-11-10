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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\OpenApi\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Property\FilterProperty;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class KeyCollectionRequestBody extends RequestBody
{
    public function __construct(
        string $columnFiltersExample = '[{"key":"name","type":"metadata.object","filterValue":1}]',
        string $sortFilterExample = '{"key":"id","direction":"ASC"}',
    ) {
        parent::__construct(
            required: true,
            content: new JsonContent(
                properties: [
                    new FilterProperty(
                        columnFiltersExample: $columnFiltersExample,
                        sortFilterExample: $sortFilterExample,
                    ),
                    new Property(
                        property: 'disabledOnly',
                        description: 'classification_store_configuration_key_collection_disabled_only',
                        type: 'boolean',
                        default: false,
                        example: false
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
