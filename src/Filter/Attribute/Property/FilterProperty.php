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

namespace Pimcore\Bundle\StudioBackendBundle\Filter\Attribute\Property;

use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;

/**
 * @internal
 */
final class FilterProperty extends Property
{
    public function __construct()
    {
        parent::__construct(
            property: 'filters',
            ref: Filter::class,
            type: 'object'
        );
    }
}
