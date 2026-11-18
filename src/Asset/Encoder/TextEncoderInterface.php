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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Encoder;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\MaxFileSizeExceededException;
use Pimcore\Model\Element\ElementInterface;

interface TextEncoderInterface
{
    public const int DEFAULT_MAX_FILE_SIZE = 2000000;

    /**
     * @throws InvalidElementTypeException|MaxFileSizeExceededException
     */
    public function encodeUTF8(ElementInterface $element): string;
}
