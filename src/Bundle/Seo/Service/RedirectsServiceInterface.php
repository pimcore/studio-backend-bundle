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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service;

use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\Redirect;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface RedirectsServiceInterface
{
    /**
     * @throws ElementSavingFailedException|InvalidArgumentException
     */
    public function addRedirect(RedirectAddParameters $parameters): Redirect;

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException
     */
    public function updateRedirect(int $id, RedirectUpdateParameters $parameters): Redirect;

    /**
     * @throws InvalidArgumentException
     */
    public function listRedirects(CollectionFilterParameter $parameters): Collection;

    /**
     * @throws NotFoundException
     */
    public function deleteRedirect(int $id): void;
}
