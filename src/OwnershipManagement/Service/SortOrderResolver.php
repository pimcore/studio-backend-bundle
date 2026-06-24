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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service;

use function in_array;
use function strtoupper;

/**
 * Resolves the ordered sort instructions (primary first, then tie-breakers) for entity collection
 * listings. Unknown fields are dropped; when nothing valid remains, the default field (descending)
 * is used so a listing is always deterministically ordered.
 *
 * @internal
 */
final readonly class SortOrderResolver implements SortOrderResolverInterface
{
    private const string DIRECTION_ASC = 'ASC';

    private const string DIRECTION_DESC = 'DESC';

    public function resolve(array $sortBy, array $sortableFields, string $defaultField): array
    {
        $resolved = [];
        foreach ($sortBy as $sort) {
            $field = $sort['field'] ?? null;
            if (!in_array($field, $sortableFields, true)) {
                continue;
            }

            $resolved[] = [
                'field' => $field,
                'direction' => $this->normalizeDirection($sort['direction'] ?? null),
            ];
        }

        if ($resolved === []) {
            return [['field' => $defaultField, 'direction' => self::DIRECTION_DESC]];
        }

        return $resolved;
    }

    private function normalizeDirection(?string $direction): string
    {
        return strtoupper((string) $direction) === self::DIRECTION_ASC
            ? self::DIRECTION_ASC
            : self::DIRECTION_DESC;
    }
}
