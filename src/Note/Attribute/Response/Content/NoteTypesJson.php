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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Attribute\Response\Content;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Note\Schema\NoteType;

/**
 * @internal
 */
final class NoteTypesJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            required: ['items'],
            properties: [
                new Property(
                    'items',
                    type: 'array',
                    items: new Items(ref: NoteType::class)
                ),
            ],
            type: 'object',
        );
    }
}
