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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
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
     * document it produces. It does retain the identifier of every collection, group, sub group and
     * key it writes, for as long as the stream runs.
     *
     * A relation naming an entity the entity sections did not carry - the configuration changed
     * between two of the reads - fails the stream at that entry, while it is being iterated: the
     * document is never closed, so what was written up to then is not valid JSON.
     *
     * @return iterable<string>
     *
     * @throws ConflictException
     * @throws DatabaseException
     */
    public function stream(StoreConfig $store): iterable;
}
