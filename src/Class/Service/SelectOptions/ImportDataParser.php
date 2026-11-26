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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions;

use const JSON_THROW_ON_ERROR;
use JsonException;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateSelectOptionParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;
use function array_is_list;
use function array_key_exists;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function json_decode;
use function sprintf;

/**
 * @internal
 */
final readonly class ImportDataParser implements ImportDataParserInterface
{
    private const array OPTION_FIELDS = [
        SelectOption::PROPERTY_VALUE,
        SelectOption::PROPERTY_LABEL,
        SelectOption::PROPERTY_NAME,
    ];

    public function parse(string $json): UpdateSelectOptionParameters
    {
        $data = $this->decode($json);
        $group = $data[Config::PROPERTY_GROUP] ?? null;
        $adminOnly = $data[Config::PROPERTY_ADMIN_ONLY] ?? false;
        $useTraits = $data[Config::PROPERTY_USE_TRAITS] ?? '';
        $implementsInterfaces = $data[Config::PROPERTY_IMPLEMENTS_INTERFACES] ?? '';
        $selectOptions = $data[Config::PROPERTY_SELECT_OPTIONS];

        $this->assertType(Config::PROPERTY_GROUP, $group, ['string', 'NULL']);
        $this->assertType(Config::PROPERTY_ADMIN_ONLY, $adminOnly, ['boolean']);
        $this->assertType(Config::PROPERTY_USE_TRAITS, $useTraits, ['string']);
        $this->assertType(Config::PROPERTY_IMPLEMENTS_INTERFACES, $implementsInterfaces, ['string']);
        $this->validateSelectOptions($selectOptions);

        return new UpdateSelectOptionParameters(
            $group,
            $adminOnly,
            $useTraits,
            $implementsInterfaces,
            $selectOptions
        );
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Uploaded file is not valid JSON: ' . $e->getMessage(), $e);
        }

        if (!is_array($data) || !array_key_exists(Config::PROPERTY_SELECT_OPTIONS, $data)) {
            throw new InvalidArgumentException(
                'Uploaded file is not a select options export: missing "selectOptions".'
            );
        }

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateSelectOptions(mixed $selectOptions): void
    {
        if (!is_array($selectOptions) || !array_is_list($selectOptions)) {
            throw new InvalidArgumentException(
                sprintf('Invalid value for "%s": expected a list of objects.', Config::PROPERTY_SELECT_OPTIONS)
            );
        }

        foreach ($selectOptions as $index => $option) {
            $path = sprintf('%s[%d]', Config::PROPERTY_SELECT_OPTIONS, $index);
            $this->assertType($path, $option, ['array']);

            foreach (self::OPTION_FIELDS as $field) {
                $this->assertType($path . '.' . $field, $option[$field] ?? '', ['string']);
            }
        }
    }

    /**
     * @param string[] $allowedTypes
     *
     * @throws InvalidArgumentException
     */
    private function assertType(string $path, mixed $value, array $allowedTypes): void
    {
        $type = gettype($value);

        if (!in_array($type, $allowedTypes, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid value for "%s": expected %s, got %s.', $path, implode(' or ', $allowedTypes), $type)
            );
        }
    }
}
