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

/**
 * @internal
 */
final class DomainConfigurationException extends AbstractApiException
{
    public function __construct()
    {
        parent::__construct(
            HttpResponseCodes::INTERNAL_SERVER_ERROR->value,
            'No main domain set in system settings, unable to generate reset password link'
        );
    }
}
