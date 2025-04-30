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

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use function sprintf;

/**
 * @internal
 */
final class ElementSavingFailedException extends AbstractApiException
{
    public function __construct(?int $id, ?string $error = null)
    {
        $message = sprintf(
            'Failed to save new element: %s',
            $error ?? 'Unknown error'
        );

        if ($id) {
            $message = sprintf(
                'Failed to save element with ID %s: %s',
                $id,
                $error ?? 'Unknown error'
            );

        }
        parent::__construct(
            HttpResponseCodes::INTERNAL_SERVER_ERROR->value,
            $message
        );
    }
}
