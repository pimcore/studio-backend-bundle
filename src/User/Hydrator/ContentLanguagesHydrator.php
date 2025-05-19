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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\AdminResolverInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class ContentLanguagesHydrator implements ContentLanguagesHydratorInterface
{
    public function __construct(
        private ToolResolverInterface $toolResolver,
        private AdminResolverInterface $adminToolResolver,
    ) {
    }

    /**
     * @return array<string>
     */
    public function hydrate(UserInterface $user): array
    {
        $validLanguages = $this->toolResolver->getValidLanguages();
        /** @var User $user */
        $contentLanguagesString = $this->adminToolResolver->reorderWebsiteLanguages($user, $validLanguages);

        return explode(',', $contentLanguagesString);
    }
}
