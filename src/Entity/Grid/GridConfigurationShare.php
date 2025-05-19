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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\Grid;

use Doctrine\ORM\Mapping as ORM;

/**
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: GridConfigurationShare::TABLE_NAME)]
class GridConfigurationShare
{
    public const string TABLE_NAME = 'bundle_studio_grid_configuration_shares';

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    private int $user;

    #[ORM\ManyToOne(targetEntity: GridConfiguration::class, inversedBy: 'shares')]
    #[ORM\JoinColumn(name: 'configuration', referencedColumnName: 'id')]
    #[ORM\Id]
    private GridConfiguration $configuration;

    public function __construct(
        int $user,
        GridConfiguration $configuration
    ) {
        $this->user = $user;
        $this->configuration = $configuration;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function getConfiguration(): GridConfiguration
    {
        return $this->configuration;
    }
}
