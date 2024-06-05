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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'AssetVersion',
    required: ['fileName'],
    type: 'object'
)]
final class AssetVersion implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'file name', type: 'string', example: 'myImageFile.png')]
        private readonly string $fileName,
        #[Property(description: 'temporary file', type: 'string', example: 'path/to/temporary/file.png')]
        private readonly ?string $temporaryFile,
    ) {
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getTemporaryFile(): string
    {
        return $this->temporaryFile;
    }
}
