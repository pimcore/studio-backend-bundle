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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Attribute\Parameter\Query;

use Attribute;
use OpenApi\Attributes\QueryParameter;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_METHOD)]
final class NoteFieldFilterParameter extends QueryParameter
{
    public function __construct()
    {
        parent::__construct(
            name: 'fieldFilters',
            description: 'JSON array of field filters. "userName" also accepts a list of names, matched exactly.',
            in: 'query',
            required: false,
            schema: new Schema(
                type: 'string',
                example: '[{"operator":"like","value":"consent-given","field":"type","type":"string"},' .
                    '{"operator":"in","value":["admin","john"],"field":"userName","type":"string"}]',
            ),
        );
    }
}
