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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\Legacy;

use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
interface ObjectExporterInterface
{
    /**
     * @param array<string, mixed> $result
     */
    public function doExportObject(Concrete $object, array &$result = []): void;
}
