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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Property\Schema\DataProperty;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Property;


final readonly class DataPropertyHydrator implements DataPropertyHydratorInterface
{
    private const ALLOWED_MODEL_PROPERTIES = [
        'key',
        'filename',
        'path',
        'id',
        'type',
    ];

    private const EXCLUDED_PROPERTIES = [
        'cid',
        'ctype',
        'cpath',
        'dao'
    ];

    public function hydrate(Property $property): DataProperty
    {
        p_r($this->extractData($property));
        die();
    }

    private function extractData(Property $property): array
    {
        $data = match (true) {
            $property->getData() instanceof Document ||
            $property->getData() instanceof Asset ||
            $property->getData() instanceof AbstractObject => $this->extractDataFromModel($property->getData()),
            default => [],
        };

        return [... $this->excludeProperties($property->getObjectVars()), ...$data];

    }

    private function extractDataFromModel(Document|Asset|AbstractObject $data): array
    {
        return array_intersect_key($data->getObjectVars(), array_flip(self::ALLOWED_MODEL_PROPERTIES));
    }

    private function excludeProperties(array $values): array {
        return array_diff_key($values, array_flip(self::EXCLUDED_PROPERTIES));
    }

}