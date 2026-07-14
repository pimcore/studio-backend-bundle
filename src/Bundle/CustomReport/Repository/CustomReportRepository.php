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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Repository;

use Exception;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config\Listing;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\CustomReportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Model\User;

/**
 * @internal
 */
final class CustomReportRepository implements CustomReportRepositoryInterface
{
    use JsonHelperTrait;

    public function __construct(
        private readonly SecurityServiceInterface $securityService,
        private readonly CustomReportResolverInterface $customReportResolver
    ) {
    }

    public function loadAll(): array
    {
        return (new Listing())->getDao()->loadList();
    }

    public function loadForUser(User $user): array
    {
        return (new Listing())->getDao()->loadForGivenUser(
            $user
        );
    }

    public function loadForCurrentUser(): array
    {
        /** @var User $currentUser */
        $currentUser = $this->securityService->getCurrentUser();

        return $this->loadForUser(
            $currentUser
        );
    }

    public function loadByName(string $name): Config
    {
        $report = null;
        $exception = null;

        try {
            $report = $this->customReportResolver->getByName($name);
        } catch (Exception $e) {
            $exception = $e;
        }

        if (!$report || $exception) {
            throw new NotFoundException(
                'Report',
                $name,
                'name',
                $exception
            );
        }

        return $report;
    }

    /**
     * {@inheritdoc}
     */
    public function loadByNameForCurrentUser(string $name): ?Config
    {
        $report = $this->loadByName($name);
        $allowedReports = $this->loadForCurrentUser();

        foreach ($allowedReports as $allowedReport) {
            if ($allowedReport->getName() === $report->getName()) {
                return $report;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function loadByNameForUser(string $name, User $user): ?Config
    {
        $report = $this->loadByName($name);
        $allowedReports = $this->loadForUser($user);

        foreach ($allowedReports as $allowedReport) {
            if ($allowedReport->getName() === $report->getName()) {
                return $report;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name): Config
    {
        $config = new Config();
        if (!$config->isWriteable()) {
            throw new NotWriteableException(
                'create',
                'Cannot create new custom report configuration: repository is not writeable.',
            );
        }

        $config->setName($name);
        $config->save();

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Config $config): Config
    {
        if (!$config->isWriteable()) {
            throw new NotWriteableException(
                'create',
                'Cannot create new custom report configuration: repository is not writeable.',
            );
        }

        $config->save();

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function cloneConfig(Config $existingConfig, string $newName): Config
    {
        $newConfig = new Config();
        if (!$newConfig->isWriteable()) {
            throw new NotWriteableException(
                'clone',
                'Cannot clone custom report configuration: repository is not writeable.',
            );
        }

        $reportData = $this->encodeJson($existingConfig);
        $reportData = $this->decodeJson($reportData);
        unset($reportData['name']);
        $reportData['name'] = $newName;

        foreach ($reportData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($newConfig, $setter)) {
                $newConfig->$setter($value);
            }
        }

        $newConfig->save();

        return $newConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Config $config): void
    {
        if (!$config->isWriteable()) {
            throw new NotWriteableException(
                'delete',
                'Cannot delete custom report configuration: repository is not writeable.',
            );
        }

        $config->delete();
    }

    public function exists(string $name): bool
    {
        try {
            $this->loadByName($name);

            return true;
        } catch (NotFoundException) {
            return false;
        }
    }
}
