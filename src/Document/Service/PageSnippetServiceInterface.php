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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Controller;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Template;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ReflectionException;

/**
 * @internal
 */
interface PageSnippetServiceInterface
{
    /**
     * @return Controller[]
     *
     * @throws ReflectionException
     */
    public function getAvailableControllers(): array;

    /**
     * @return Template[]
     */
    public function getAvailableTemplates(): array;
}
