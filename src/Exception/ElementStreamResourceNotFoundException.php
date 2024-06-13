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

namespace Pimcore\Bundle\StudioBackendBundle\Exception;

use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;

/**
 * @internal
 */
final class ElementStreamResourceNotFoundException extends AbstractApiException
{
    public function __construct(int $id, string $type = 'Element')
    {
        parent::__construct(
            HttpResponseCodes::NOT_FOUND->value,
            sprintf(
                'Unable to get resource for %s with ID %d',
                $type,
                $id
            )
        );
    }
}