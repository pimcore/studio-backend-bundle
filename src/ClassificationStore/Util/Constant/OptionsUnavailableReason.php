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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant;

/**
 * Why a key's resolved options are not published, as the coded value the schema carries.
 *
 * @internal
 */
enum OptionsUnavailableReason: string
{
    case PROVIDER_NOT_STATIC = 'providerNotStatic';
    case RESOLUTION_FAILED = 'resolutionFailed';
}
