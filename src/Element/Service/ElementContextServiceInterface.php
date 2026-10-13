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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

/**
 * @internal
 */
interface ElementContextServiceInterface
{
    /**
     * Runs $callback with unpublished objects and documents visible and localized fallback values off, as in
     * Studio API requests, and restores the previous values afterwards, also when $callback throws.
     * Used where Studio logic runs outside an API request, e.g. in a messenger worker that boots with the
     * CLI context. Admin mode is deliberately left as it is: in a process without a request it would make
     * field definition enrichment run (e.g. dynamic options providers). Without it, getters of classes that
     * implement PreGetValueHookInterface still return the hook's value instead of the stored one.
     *
     * @param-immediately-invoked-callable $callback
     *
     * @param callable(): void $callback
     */
    public function runInStudioContext(callable $callback): void;
}
