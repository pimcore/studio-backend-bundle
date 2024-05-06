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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementPermissionTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Dependency;

/**
 * @internal
 */
final readonly class Repository implements RepositoryInterface
{
    use ElementProviderTrait;
    use ElementPermissionTrait;

    public function __construct(
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    public function listDependencies(string $elementType, int $elementId): Dependency
    {
        return $this->getElement($this->serviceResolver, $elementType, $elementId)->getDependencies();
    }
}