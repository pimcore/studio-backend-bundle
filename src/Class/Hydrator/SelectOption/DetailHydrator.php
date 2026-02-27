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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\SelectOption;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionData;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionDetail;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;

/**
 * @internal
 */
final readonly class DetailHydrator implements DetailHydratorInterface
{
    public function hydrate(Config $config, bool $isWriteable): SelectOptionDetail
    {
        return new SelectOptionDetail(
            $config->getId(),
            $config->getGroup(),
            $config->getAdminOnly(),
            $config->getUseTraits(),
            $config->getImplementsInterfaces(),
            $this->hydrateSelectOptions($config->getSelectOptions()),
            $config->getEnumName(true),
            $isWriteable,
        );
    }

    /**
     * @param SelectOption[] $selectOptions
     *
     * @return SelectOptionData[]
     */
    private function hydrateSelectOptions(array $selectOptions): array
    {
        return array_map(
            static fn (SelectOption $option): SelectOptionData => new SelectOptionData(
                $option->getValue(),
                $option->getLabel(),
                $option->getName(),
            ),
            $selectOptions,
        );
    }
}
