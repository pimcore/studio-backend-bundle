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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\MappedParameter\McpServerParameter;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Schema\McpServer;

/**
 * @internal
 */
interface McpServerConfigurationServiceInterface
{
    /**
     * @return list<McpServer>
     */
    public function listConfigurations(): array;

    /**
     * @throws NotFoundException
     */
    public function getConfiguration(string $id): McpServer;

    /**
     * @throws ElementExistsException|ElementSavingFailedException|NotWriteableException
     */
    public function saveConfiguration(McpServerParameter $parameter): McpServer;

    /**
     * @throws ElementSavingFailedException|NotFoundException|NotWriteableException
     */
    public function updateConfiguration(string $id, McpServerParameter $parameter): McpServer;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function deleteConfiguration(string $id): void;
}
