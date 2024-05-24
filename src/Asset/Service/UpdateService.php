<?php

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class UpdateService implements UpdateServiceInterface
{
    use ElementProviderTrait;

    private array $updateAdapters;
    public function __construct(
        #[TaggedIterator('pimcore.studio_backend.update_adapter')] iterable $updateAdapters,
        private readonly ServiceResolver $serviceResolver
    )
    {
        $this->updateAdapters = [...$updateAdapters];
    }

    public function update(string $elementType, int $id, array $data)
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        foreach($this->updateAdapters as $adapter) {
            if(array_key_exists($adapter->getDataIndex(), $data)) {
                $adapter->update($element, $data[$adapter->getDataIndex()]);
            }
        }
        $element->save();
    }
}