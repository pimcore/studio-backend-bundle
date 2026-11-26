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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration;

use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * One committed key write, and the definition the row carried when that write took its lock
 * ({@see KeyRepositoryInterface::update()}).
 *
 * The replaced definition travels out with the write because it is the only value a comparison of
 * the key's resolved search index flag may be made against. A definition read by the caller before
 * the write belongs to a snapshot a competing writer can have replaced in between: two requests
 * that both read `indexed: true`, one storing false and one storing true, would have the second
 * compare true against its own stale true, conclude that nothing moved and queue no remap - leaving
 * the mapping on the first request's answer while the store carries the second's.
 *
 * @internal
 */
final readonly class KeyWriteResult
{
    public function __construct(
        private KeyConfig $config,
        private ?string $definitionBefore,
    ) {
    }

    public function getConfig(): KeyConfig
    {
        return $this->config;
    }

    /**
     * The definition the row carried when the write took its lock, as the column holds it. Null
     * when there was no row to hold - a key the write created replaced no definition at all.
     */
    public function getDefinitionBefore(): ?string
    {
        return $this->definitionBefore;
    }
}
