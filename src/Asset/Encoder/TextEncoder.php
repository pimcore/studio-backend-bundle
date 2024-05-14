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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Encoder;

use ForceUTF8\Encoding;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\MaxFileSizeExceededException;
use Pimcore\Model\Asset\Text;
use Pimcore\Model\Element\ElementInterface;

final class TextEncoder implements TextEncoderInterface
{
    private const MAX_FILE_SIZE = 2000000;

    /**
     * @throws InvalidElementTypeException|MaxFileSizeExceededException
     */
    public function encodeUTF8(ElementInterface $element): string
    {
        if (!$element instanceof Text) {
            throw new InvalidElementTypeException('Element must be an instance of Text');
        }

        if ($element->getFileSize() < self::MAX_FILE_SIZE) {
            throw new MaxFileSizeExceededException(self::MAX_FILE_SIZE);
        }

        return Encoding::toUTF8($element->getData());
    }
}
