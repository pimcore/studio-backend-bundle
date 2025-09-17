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

use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\RenderAreaBlockParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Controller;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\RenderAreaBlockData;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Template;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ReflectionException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException|UserNotFoundException
     */
    public function renderAreaBlock(
        int $documentId,
        Request $request,
        RenderAreaBlockParameter $parameter
    ): RenderAreaBlockData;

    /**
     * @throws ElementSavingFailedException|ForbiddenException
     * @throws InvalidArgumentException|NotFoundException|UserNotFoundException
     */
    public function setMainDocument(int $documentId, ?string $mainDocumentPath = null): void;
}
