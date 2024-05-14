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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Property;

use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class KeyValueArray extends Property
{
    public function __construct(string $propertyName = 'items')
    {
        parent::__construct(
            property: $propertyName,
            type: 'object',
            additionalProperties: new AdditionalProperties(type: 'string')
        );
    }
}
