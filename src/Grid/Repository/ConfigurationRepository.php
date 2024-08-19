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


namespace Pimcore\Bundle\StudioBackendBundle\Grid\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final readonly class ConfigurationRepository implements ConfigurationRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecurityServiceInterface $securityService
    )
    {
    }

    public function create(GridConfiguration $configuration): GridConfiguration
    {
        $configuration->setCreated();
        $configuration->setOwner($this->securityService->getCurrentUser()->getId());

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        return $configuration;
    }

    public function update(GridConfiguration $configuration): GridConfiguration
    {

        $configuration->setModified();

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        return $configuration;
    }
}