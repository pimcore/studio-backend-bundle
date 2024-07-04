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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response;

use Attribute;
use OpenApi\Attributes\Response;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
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
