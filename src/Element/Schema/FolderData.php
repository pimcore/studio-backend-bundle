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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'FolderData',
    title: 'Folder Data',
    description: 'Folder Data Scheme for API',
    required: ['folderName'],
    type: 'object'
)]
final readonly class FolderData
{
    public function __construct(
        #[Property(description: 'Folder Name', type: 'string', example: 'Awesome stuff inside')]
        private string $folderName
    ) {
    }

    public function getFolderName(): string
    {
        return $this->folderName;
    }
}
