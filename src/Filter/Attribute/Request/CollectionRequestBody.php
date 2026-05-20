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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Property\FilterProperty;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class CollectionRequestBody extends RequestBody
{
    public function __construct(
        int $pageExample = 1,
        int $pageSizeExample = 50,
        string $columnFiltersExample = '[{"key":"name","type":"metadata.object","filterValue":1}]',
        string $sortFilterExample = '{"key":"id","direction":"ASC"}',
        string $additionalFiltersExample = '[]'
    ) {
        parent::__construct(
            required: true,
            content: new JsonContent(
                properties: [
                    new FilterProperty(
                        pageExample: $pageExample,
                        pageSizeExample: $pageSizeExample,
                        columnFiltersExample: $columnFiltersExample,
                        sortFilterExample: $sortFilterExample,
                        additionalFiltersExample: $additionalFiltersExample
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
