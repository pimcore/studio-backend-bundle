<?php

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolver;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Element\DuplicateFullPathException;

/**
 * @internal
 */
final class UpdateService implements UpdateServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private readonly AdapterLoaderInterface $adapterLoader,
        private readonly ServiceResolver $serviceResolver
    )
    {
    }

    /**
     * @throws ElementSavingFailedException|ElementNotFoundException
     */
    public function update(string $elementType, int $id, array $data): void
    {
        $element = $this->getElement($this->serviceResolver, $elementType, $id);

        foreach ($this->adapterLoader->loadAdapters() as $adapter) {
            if (array_key_exists($adapter->getDataIndex(), $data)) {
                $adapter->update($element, $data);
            }
        }
        try {
            $element->save();
        } catch (DuplicateFullPathException $e) {
            throw new ElementSavingFailedException($id, $e->getMessage());
        }
    }
}
