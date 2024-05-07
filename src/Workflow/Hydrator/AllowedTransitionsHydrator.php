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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\AllowedTransitions;

/**
 * @internal
 */
final class AllowedTransitionsHydrator implements AllowedTransitionsHydratorInterface
{
    /**
     * @return AllowedTransitions[]
     */
    public function hydrate(array $allowedTransitions): array
    {
        $hydrated = [];
        foreach ($allowedTransitions as $transition) {
            $hydrated[] = new AllowedTransitions(
                $transition['name'],
                $transition['label'],
                $transition['iconCls'],
                $transition['objectLayout'],
                $transition['notes'],
                $transition['unsavedChangesBehaviour'],
            );
        }

        return $hydrated;
    }

}