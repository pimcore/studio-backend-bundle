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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_array;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class DateRangeAdapter implements SetterDataInterface
{
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        ?FieldContextData $contextData = null
    ): ?CarbonPeriod {

        $dateData = $data[$key];
        if (is_array($dateData) && isset($dateData['start_date'], $dateData['end_date'])) {
            $startDate = $this->getDateFromTimestamp($data['start_date'] / 1000);
            $endDate = $this->getDateFromTimestamp($data['end_date'] / 1000);

            return CarbonPeriod::create($startDate, $endDate);
        }

        return null;
    }

    private function getDateFromTimestamp(float|int|string $timestamp): Carbon
    {
        $date = new Carbon();
        $date->setTimestamp($timestamp);

        return $date;
    }
}
