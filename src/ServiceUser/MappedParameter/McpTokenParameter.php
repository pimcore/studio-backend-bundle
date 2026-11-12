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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\McpTokenServiceInterface;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @internal
 */
#[Schema(
    schema: 'ServiceUserMcpTokenRequest',
    title: 'Issue MCP Access Token',
    description: 'Lifetime of a new MCP access token for a service user',
    type: 'object'
)]
final readonly class McpTokenParameter
{
    public function __construct(
        #[Property(description: 'Lifetime in days, 1 to 90', type: 'integer', example: 30)]
        #[Range(min: 1, max: McpTokenServiceInterface::MAX_TTL_DAYS)]
        private int $ttlDays = 30,
    ) {
    }

    public function getTtlDays(): int
    {
        return $this->ttlDays;
    }
}
