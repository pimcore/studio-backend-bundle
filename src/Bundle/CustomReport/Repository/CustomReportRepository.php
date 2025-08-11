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
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportUpdate;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\User;

/**
 * @internal
 */
final readonly class CustomReportRepository implements CustomReportRepositoryInterface
{
    public function __construct(
        private AdapterServiceInterface $adapterService,
        private SecurityServiceInterface $securityService,
        private CustomReportResolverInterface $customReportResolver
    ) {
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
    public function update(Config $config, CustomReportUpdate $parameters): Config
    {
        if (!$config->isWriteable()) {
            throw new NotWriteableException(
                'create',
                'Cannot create new custom report configuration: repository is not writeable.',
            );
        }

        $adapter = $this->adapterService->getAdapter($config);

        $config->setSql($parameters->getSql());
        $config->setColumnConfiguration($parameters->getColumnConfigurations());
        $config->setDataSourceConfig($parameters->getDataSourceConfig());
        $config->setNiceName($parameters->getNiceName());
        $config->setGroup($parameters->getGroup());
        $config->setGroupIconClass($parameters->getGroupIconClass());
        $config->setIconClass($parameters->getIconClass());
        $config->setMenuShortcut($parameters->getMenuShortcut());
        $config->setReportClass($parameters->getReportClass());
        $config->setChartType($parameters->getChartType());
        $config->setPieColumn($parameters->getPieColumn());
        $config->setPieLabelColumn($parameters->getPieLabelColumn());
        $config->setXAxis($parameters->getXAxis());
        $config->setYAxis($parameters->getYAxis());
        $config->setShareGlobally($parameters->getSharedGlobally());
        $config->setSharedUserNames($parameters->getSharedUserNames());
        $config->setSharedRoleNames($parameters->getSharedRoleNames());
        $config->setPagination($adapter->getPagination());

        $config->save();

        return $config;
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
