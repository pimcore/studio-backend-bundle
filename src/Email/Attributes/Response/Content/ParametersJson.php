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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Attributes\Response\Content;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\BlocklistEntry;
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
