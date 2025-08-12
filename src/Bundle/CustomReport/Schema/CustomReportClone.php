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
    schema: 'BundleCustomReportClone',
    title: 'Bundle Custom Report Clone',
    required: ['newName'],
    type: 'object'
)]
final readonly class CustomReportClone
{
    public function __construct(
        #[Property(description: 'New name the cloned custom report', type: 'string', example: 'myNewReport')]
        private string $newName,
    ) {
    }

    public function getNewName(): string
    {
        return $this->newName;
    }
}
