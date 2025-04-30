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

namespace Pimcore\Bundle\StudioBackendBundle\Exception\Api;

use function sprintf;

/**
 * @internal
 */
final class ElementDeletingFailedException extends AbstractApiException
{
    public function __construct(int $id, ?string $error = null)
    {
        parent::__construct(
            500,
            sprintf('Failed to delete element with ID %s: %s', $id, $error ?? 'Unknown error')
        );
    }
}
