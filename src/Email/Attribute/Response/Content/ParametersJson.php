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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Attribute\Response\Content;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailLogEntryParameter;

/**
 * @internal
 */
final class ParametersJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['data'],
            properties: [
                new Property(
                    'data',
                    title: 'data',
                    description: 'Email log entry parameters',
                    type: 'array',
                    items: new Items(ref: EmailLogEntryParameter::class)
                ),
            ],
            type: 'object',
        );
    }
}
