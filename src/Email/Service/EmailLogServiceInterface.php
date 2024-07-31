<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Email\Service;

use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailLogEntryDetail;
use Pimcore\Bundle\StudioBackendBundle\Email\Schema\EmailLogEntryParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Mail;
use Pimcore\Model\Tool\Email\Log;

/**
 * @internal
 */
interface EmailLogServiceInterface
{
    public function listEntries(CollectionParameters $parameters): Collection;

    /**
     * @throws NotFoundException
     */
    public function getEntry(int $id): Log;

    /**
     * @throws NotFoundException
     */
    public function getEntryDetails(int $id): EmailLogEntryDetail;

    /**
     * @throws NotFoundException
     */
    public function getEntryText(int $id): string;

    /**
     * @throws NotFoundException
     */
    public function getEntryHtml(int $id): string;

    /**
     * @throws NotFoundException
     * @return EmailLogEntryParameter[]
     */
    public function getEntryParams(int $id): array;

    /**
     * @throws NotFoundException
     */
    public function deleteEntry(int $id): void;

    /**
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function getEmailFromLogEntry(Log $emailLogEntry): Mail;
}
