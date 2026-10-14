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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\Data;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Encoder\TextEncoderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\MaxFileSizeExceededException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;

/**
 * @internal
 */
final class TextService implements TextServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private readonly SecurityServiceInterface $securityService,
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly TextEncoderInterface $textEncoder,
    ) {
    }

    /**
     * @throws ForbiddenException|NotFoundException|InvalidElementTypeException|MaxFileSizeExceededException
     */
    public function getUTF8EncodedData(int $id): string
    {
        $element = $this->getElement($this->serviceResolver, 'asset', $id);
        $this->securityService->hasElementPermission(
            $element,
            $this->securityService->getCurrentUser(),
            ElementPermissions::VIEW_PERMISSION
        );

        return $this->textEncoder->encodeUTF8($element);
    }
}
