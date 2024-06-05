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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Updater\Adapter;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundByPathException;
use Pimcore\Bundle\StudioBackendBundle\Updater\Adapter\UpdateAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class CustomMetadataAdapter implements UpdateAdapterInterface
{
    private const INDEX_KEY = 'metadata';

    use ElementProviderTrait;

    public function __construct(private ServiceResolverInterface $serviceResolver)
    {
    }

    public function update(ElementInterface $element, array $data): void
    {
        if (!$element instanceof Asset || !array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $metadataValues = [];

        foreach ($data[$this->getIndexKey()] as  $metadata) {
            $md = $metadata;
            $md['data'] = $this->resolveData($metadata['type'], $metadata['data']);
            $metadataValues[] = $md;
        }

        $element->setMetadata($metadataValues);
    }

    public function getIndexKey(): string
    {
        return self::INDEX_KEY;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }

    private function resolveData(string $type, mixed $data): mixed
    {
       return match($type) {
           'asset', 'document', 'object' => $this->resolveElement($type, $data),
           default => $data,
       };
    }

    private function resolveElement(string $type, string $path): ?int
    {
        try {
            return $this->getElementByPath($this->serviceResolver, $type, $path)->getId();
        } catch (ElementNotFoundByPathException) {
            return null;
        }
    }
}
