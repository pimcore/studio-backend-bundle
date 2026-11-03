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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * @internal
 */
interface ArtifactBuilderInterface
{
    /**
     * The store's artifact as the pieces of one JSON document, each section walked in pages and
     * written as it is read, so that neither an export over HTTP nor one on the console holds the
     * document it produces.
     *
     * @return iterable<string>
     *
     * @throws DatabaseException
     */
    public function stream(StoreConfig $store): iterable;
}
