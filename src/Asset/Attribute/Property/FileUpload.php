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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property;

use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class FileUpload extends Property
{
    public function __construct(string $name = 'file')
    {
        parent::__construct(
            $name,
            description: 'File to upload',
            type: 'string',
            format: 'binary'
        );
    }
}
