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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Error;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Schemas;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;

#[Attribute(Attribute::TARGET_METHOD)]
final class UnsupportedMediaTypeResponse extends Response
{
    public function __construct()
    {
        parent::__construct(
            response: HttpResponseCodes::UNSUPPORTED_MEDIA_TYPE->value,
            description: 'Unsupported Media Type',
            content: new JsonContent(
                oneOf: array_map(static function ($class) {
                    return new Schema(ref: $class);
                }, Schemas::ERRORS),
            )
        );
    }
}
