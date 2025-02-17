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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SearchPreviewDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Date;
use Pimcore\Model\DataObject\ClassDefinition\Data\Datetime;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function is_string;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class DateAdapter implements SetterDataInterface, SearchPreviewDataInterface
{
    public function __construct(private DataServiceInterface $dataService)
    {
    }

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?Carbon {
        $dateData = $data[$key];

        if (is_numeric($dateData)) {
            /** @var Date|Datetime $fieldDefinition */
            return $fieldDefinition->denormalize($dateData);
        }

        if (is_string($dateData)) {
            return Carbon::parse($dateData);
        }

        return null;
    }

    public function getPreviewFieldData(
        mixed $value,
        Data $fieldDefinition,
        array $data
    ): array {
        if (!$fieldDefinition instanceof Date) {
            return $data;
        }

        $key = $this->dataService->getPreviewFieldName($fieldDefinition);
        $data[$key] = $fieldDefinition->normalize($value);

        return $data;
    }
}
