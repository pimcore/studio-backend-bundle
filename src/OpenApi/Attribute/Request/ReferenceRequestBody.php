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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\RequestBody;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ReferenceRequestBody extends RequestBody
{
    public function __construct(string $referenceClass)
    {
        parent::__construct(
            required: true,
            content: new JsonContent(ref: $referenceClass)
        );
    }
}
