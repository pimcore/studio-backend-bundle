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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Error;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Schemas;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;

#[Attribute(Attribute::TARGET_METHOD)]
final class NotFoundResponse extends Response
{
    public function __construct()
    {
        parent::__construct(
            response: HttpResponseCodes::NOT_FOUND->value,
            description: 'Resource not found',
            content: new JsonContent(
                oneOf: array_map(static function ($class) {
                    return new Schema(ref: $class);
                }, Schemas::ERRORS),
            )
        );
    }
}
