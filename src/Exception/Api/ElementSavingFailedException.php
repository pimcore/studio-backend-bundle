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
