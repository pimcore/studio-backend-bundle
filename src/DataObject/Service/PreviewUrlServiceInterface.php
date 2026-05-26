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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\PreviewParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface PreviewUrlServiceInterface
{
    /**
     * @param array<string, mixed> $additionalParams
     *
     * @throws InvalidArgumentException|NotFoundException
     */
    public function getPreviewUrl(PreviewParameter $parameter, array $additionalParams = []): string;
}
