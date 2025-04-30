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
use OpenApi\Attributes\Header;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;

#[Attribute(Attribute::TARGET_METHOD)]
final class RedirectResponse extends Response
{
    public function __construct(string $description = 'Redirect')
    {
        parent::__construct(
            response: HttpResponseCodes::REDIRECT->value,
            description: $description,
            headers: [
                new Header(
                    header: 'Location',
                    description: 'Redirect location',
                    required: true,
                    schema: new Schema(
                        type: 'string',
                        format: 'uri'
                    )
                ),
            ]
        );
    }
}
