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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\StoreConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig\Listing;
use function sprintf;

/**
 * @internal
 */
final readonly class StoreRepository implements StoreRepositoryInterface
{
    public function __construct(
        private StoreConfigResolverInterface $storeConfigResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listStores(): array
    {
        return (new Listing())->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getById(int $id): StoreConfig
    {
        $config = $this->storeConfigResolver->getById($id);

        if (!$config) {
            throw new NotFoundException('store configuration', $id);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name): StoreConfig
    {
        $existing = $this->storeConfigResolver->getByName($name);

        if ($existing) {
            throw new InvalidArgumentException(
                sprintf('Store with the name "%s" already exists', $name)
            );
        }

        $config = new StoreConfig();
        $config->setName($name);

        try {
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, string $name, ?string $description): StoreConfig
    {
        $config = $this->getById($id);

        $existing = $this->storeConfigResolver->getByName($name);
        if ($existing && $existing->getId() !== $id) {
            throw new InvalidArgumentException(
                sprintf('Store with the name "%s" already exists', $name)
            );
        }

        $config->setName($name);
        $config->setDescription($description);

        try {
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage(), $e);
        }

        return $config;
    }
}
