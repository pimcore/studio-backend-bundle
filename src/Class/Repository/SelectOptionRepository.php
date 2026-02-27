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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Repository;

use Exception;
use InvalidArgumentException;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\SelectOptions\ConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Config\Listing;
use RuntimeException;
use function sprintf;

/**
 * @internal
 */
final readonly class SelectOptionRepository implements SelectOptionRepositoryInterface
{
    private const string NOT_WRITEABLE_EXCEPTION_MESSAGE = 'Select Option';

    public function __construct(
        private ConfigResolverInterface $configResolver,
    ) {
    }

    public function listSelectOptions(): array
    {
        return (new Listing())->load();
    }

    public function getById(string $id): Config
    {
        $config = $this->configResolver->getById($id);

        if ($config === null) {
            throw new NotFoundException(type: 'Select Option', id: $id);
        }

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $id): Config
    {
        $listing = new Listing();

        if ($listing->hasConfig($id)) {
            throw new ElementExistsException(
                sprintf(
                    'Select options with the same ID already exists (lower/upper cases may be different): %s',
                    $id
                )
            );
        }

        try {
            $config = $this->configResolver->createFromData([
                Config::PROPERTY_ID => $id,
            ]);
        } catch (InvalidArgumentException | RuntimeException $e) {
            throw new ApiInvalidArgumentException(message: $e->getMessage(), previous: $e);
        }

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
    public function save(Config $config): void
    {
        if (!$this->isWriteable($config)) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        }

        try {
            $config->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException(null, $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Config $config): void
    {
        if (!$this->isWriteable($config)) {
            throw new NotWriteableException(self::NOT_WRITEABLE_EXCEPTION_MESSAGE);
        }

        try {
            $config->delete();
        } catch (RuntimeException $e) {
            throw new ConflictException($e->getMessage(), $e);
        }
    }

    public function isWriteable(Config $config): bool
    {
        return $config->isWriteable();
    }

    public function getFieldsUsedIn(Config $config): array
    {
        return $config->getFieldsUsedIn();
    }
}
