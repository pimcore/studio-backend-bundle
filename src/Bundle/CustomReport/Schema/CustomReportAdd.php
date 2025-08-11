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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleCustomReportAdd',
    title: 'Bundle Custom Report Add',
    required: ['name'],
    type: 'object'
)]
final readonly class CustomReportAdd
{
    public function __construct(
        #[Property(description: 'Name of the custom report', type: 'string', example: 'my-report')]
        private string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
