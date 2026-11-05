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

namespace Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\CreateFromUserParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\UpdateParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUser;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserCredentials;

/**
 * Manages service users and their bound OAuth client.
 *
 * @internal
 */
interface ServiceUserServiceInterface
{
    public function listServiceUsers(): Collection;

    /**
     * @throws NotFoundException
     */
    public function getServiceUser(int $id): ServiceUser;

    /**
     * Creates the service user and its OAuth client. The client secret is returned only here.
     *
     * @throws ElementExistsException|InvalidArgumentException|NotFoundException|ParseException|DatabaseException
     */
    public function createServiceUser(CreateParameter $parameter): ServiceUserCredentials;

    /**
     * Creates a service user with the permissions, roles, class and document type restrictions
     * and workspaces of a human user. The human user is not changed.
     *
     * @throws ElementExistsException|ForbiddenException|InvalidArgumentException|NotFoundException|DatabaseException
     */
    public function createServiceUserFromUser(int $userId, CreateFromUserParameter $parameter): ServiceUserCredentials;

    /**
     * @throws InvalidArgumentException when a name, password, admin or two-factor field is sent
     * @throws NotFoundException|ParseException|DatabaseException
     */
    public function updateServiceUser(int $id, UpdateParameter $parameter): ServiceUser;

    /**
     * The service user is refused from its next request on.
     *
     * @throws NotFoundException|DatabaseException
     */
    public function deactivateServiceUser(int $id): ServiceUser;

    /**
     * Deletes the service user; its client, tokens and MCP server grants go with it.
     *
     * @throws NotFoundException|DatabaseException
     */
    public function deleteServiceUser(int $id): void;

    /**
     * Replaces the client secret; the previous one stops working at once.
     *
     * @throws NotFoundException
     */
    public function rotateSecret(int $id): ServiceUserCredentials;
}
