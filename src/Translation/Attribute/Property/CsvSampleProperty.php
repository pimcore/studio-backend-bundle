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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Property;

use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class CsvSampleProperty extends Property
{
    public function __construct() {
        parent::__construct(
            property: 'csvSettings',
            properties: [
                new Property(
                    property: 'delimiter',
                    type: 'string',
                    example: ';'
                ),
                new Property(
                    property: 'quoteChar',
                    type: 'string',
                    example: '"'
                ),
                new Property(
                    property: 'escapeChar',
                    type: 'string',
                    example: '\\'
                ),
                new Property(
                    property: 'getLineTerminator',
                    type: 'string',
                    example: ''
                ),
            ],
            type: 'object'
        );
    }
}
