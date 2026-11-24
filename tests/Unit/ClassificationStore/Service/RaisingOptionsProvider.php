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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Exception;
use Throwable;

/**
 * A provider whose configuration is gone, which is how the core providers report one.
 *
 * Named rather than anonymous: core resolves a provider by class name
 * ({@see \Pimcore\Model\DataObject\ClassDefinition\Helper\OptionsProviderResolver}), so a test that
 * goes through the enrichment cannot hand over an instance of its own.
 *
 * @internal
 */
final class RaisingOptionsProvider extends AbstractThrowingProvider
{
    protected function failure(): Throwable
    {
        return new Exception('Missing select options configuration');
    }
}
