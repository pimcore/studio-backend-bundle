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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service;

use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Util\TransferableProperties;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use function array_is_list;
use function gettype;
use function implode;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final readonly class TransferDataValidator implements TransferDataValidatorInterface
{
    public function validate(array $data): void
    {
        foreach (TransferableProperties::filter($data) as $property => $value) {
            $allowedTypes = TransferableProperties::TYPES[$property];
            $type = gettype($value);

            if (!in_array($type, $allowedTypes, true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid value for "%s": expected %s, got %s.',
                        $property,
                        implode(' or ', $allowedTypes),
                        $type
                    )
                );
            }

            if (in_array($property, TransferableProperties::STRING_LIST_PROPERTIES, true)) {
                $this->validateStringList($property, $value);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateStringList(string $property, array $value): void
    {
        if (!array_is_list($value)) {
            throw new InvalidArgumentException(
                sprintf('Invalid value for "%s": expected a list of strings.', $property)
            );
        }

        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid value for "%s": expected a list of strings, got %s.', $property, gettype($item))
                );
            }
        }
    }
}
