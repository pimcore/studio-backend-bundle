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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class NotesCollectionRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['page', 'pageSize'],
                properties: [
                    new Property(
                        property: 'page',
                        type: 'integer',
                        example: 1
                    ),
                    new Property(
                        property: 'pageSize',
                        type: 'integer',
                        example: 50
                    ),
                    new Property(
                        property: 'sortOrder',
                        description: 'Sort order (asc or desc).',
                        type: 'string',
                        enum: [
                            'ASC',
                            'DESC',
                        ],
                        example: 'ASC'
                    ),
                    new Property(
                        property: 'sortBy',
                        description: 'Sort by field. Only works in combination with sortOrder.',
                        type: 'string',
                        enum: [
                            'id',
                            'type',
                            'cId',
                            'cType',
                            'cPath',
                            'date',
                            'title',
                            'description',
                            'locked',
                        ],
                        example: 'id',
                    ),
                    new Property(
                        property: 'filter',
                        description: 'Filter for notes',
                        type: 'string',
                        example: ''
                    ),
                    new Property(
                        property: 'fieldFilters',
                        description: 'Filter for specific fields, will be json decoded to an array. e.g.
                        [{"operator":"like","value":"John","field":"name","type":"string"}]',
                        type: 'object',
                        example: '[{"operator":"like","value":"consent-given","field":"type","type":"string"}]'
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
