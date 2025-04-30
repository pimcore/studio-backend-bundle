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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response;

use Attribute;
use OpenApi\Attributes\Response;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;

#[Attribute(Attribute::TARGET_METHOD)]
final class SuccessResponse extends Response
{
    public function __construct(string $description = 'Success', mixed $content = null, ?array $headers = null)
    {
        parent::__construct(
            response: HttpResponseCodes::SUCCESS->value,
            description: $description,
            headers: $headers,
            content: $content
        );
    }
}
