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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GetPageResponse;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\GroupInfoResolverTrait;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * @internal
 */
final readonly class KeyHydrator implements KeyHydratorInterface
{
    use GroupInfoResolverTrait;

    public function hydrateKeyDetail(KeyConfig $keyConfig): KeyDetail
    {
        return new KeyDetail(
            $keyConfig->getId(),
            $keyConfig->getName(),
            $keyConfig->getStoreId(),
            $keyConfig->getType(),
            $keyConfig->getEnabled(),
            $keyConfig->getDescription(),
            $this->getKeyDefinition($keyConfig),
            $keyConfig->getCreationDate(),
            $keyConfig->getModificationDate(),
        );
    }

    public function hydrateGetPageResponse(int $page): GetPageResponse
    {
        return new GetPageResponse($page);
    }
}
