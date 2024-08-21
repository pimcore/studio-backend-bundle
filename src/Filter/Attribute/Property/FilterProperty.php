<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
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