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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture;

use ArrayObject;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use function in_array;

/**
 * A many-to-many relation whose visible field sources are given instead of read from the class definitions
 * and predefined metadata (both need the database). Everything else - which names apply to which element,
 * the element properties, the asset system fields - is the relation's own logic. Asset metadata values,
 * which need the metadata data type services, are taken from the given map and record the language they
 * were requested in.
 *
 * @internal
 */
final class VisibleFieldsManyToManyRelation extends ManyToManyRelation
{
    /**
     * Shared with clones, so requests made through a copy of the definition are recorded as well.
     *
     * @var ArrayObject<string, string|null>
     */
    public readonly ArrayObject $requestedMetadataLanguages;

    /**
     * @param array<string, string[]> $stubSources
     * @param array<string, mixed> $stubAssetMetadata
     * @param array<string, array<string, mixed>> $stubAvailableVisibleFields
     */
    public function __construct(
        private readonly array $stubSources = [],
        private readonly array $stubAssetMetadata = [],
        private readonly array $stubAvailableVisibleFields = [],
    ) {
        $this->requestedMetadataLanguages = new ArrayObject();
    }

    public function getVisibleFieldSources(array $context = []): array
    {
        return $this->stubSources;
    }

    public function getAvailableVisibleFields(array $context = []): array
    {
        return $this->stubAvailableVisibleFields;
    }

    protected function resolveAssetVisibleFieldValue(Asset $asset, string $name, array $params = []): mixed
    {
        if (in_array($name, ['filename', 'mimetype'], true)) {
            return parent::resolveAssetVisibleFieldValue($asset, $name, $params);
        }

        $this->requestedMetadataLanguages[$name] = $params['language'] ?? null;

        return $this->stubAssetMetadata[$name] ?? null;
    }
}
