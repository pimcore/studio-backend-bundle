<?php

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

namespace Pimcore\Bundle\StudioBackendBundle\Extractor\Element;

use Pimcore\Model\AbstractModel;
use Pimcore\Model\Element\ElementInterface;

class DataExtractor implements DataExtractorInterface
{
    private const ALLOWED_MODEL_PROPERTIES = [
        'key',
        'filename',
        'path',
        'id',
        'type',
    ];

    public function extractData(ElementInterface $element): array
    {
        /**
         * @var AbstractModel $element
         */
        $data = array_intersect_key(
            $element->getObjectVars(),
            array_flip(self::ALLOWED_MODEL_PROPERTIES)
        );

        $data['fullPath'] = $element->getFullPath();

        return $data;
    }
}
