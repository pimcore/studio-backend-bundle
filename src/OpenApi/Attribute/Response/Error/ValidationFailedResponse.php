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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ValidationFailedError;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ValidationFailedResponse extends Response
{
    public function __construct()
    {
        parent::__construct(
            response: HttpResponseCodes::UNPROCESSABLE_CONTENT->value,
            description: 'Element validation failed. The optional validationErrors list the individual errors.',
            content: new JsonContent(ref: ValidationFailedError::class),
        );
    }
}
