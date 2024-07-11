<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\MetaData\Repository;


use Pimcore\Model\Metadata\Predefined;

/**
 * @internal
 */
interface MetaDataRepositoryInterface
{
    /**
     * @return Predefined[]
     */
    public function getAllPredefinedMetaData(): array;
}