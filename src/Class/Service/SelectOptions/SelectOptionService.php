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

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use InvalidArgumentException;
use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\SelectOptions\ConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\SelectOption\DetailEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\SelectOption\UsageItemEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\SelectOption\DetailHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateSelectOptionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateSelectOptionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\SelectOptionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption\SelectOptionUsageItem;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\JsonExport;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\SelectOptions\Config;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function is_array;
use function json_encode;
use function sprintf;

/**
 * @internal
 */
final readonly class SelectOptionService implements SelectOptionServiceInterface
{
    public function __construct(
        private SelectOptionRepositoryInterface $selectOptionRepository,
        private ConfigResolverInterface $configResolver,
        private DetailHydratorInterface $detailHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
        private ImportDataParserInterface $importDataParser,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectOption(string $id): SelectOptionDetail
    {
        $config = $this->selectOptionRepository->getById($id);

        return $this->hydrateDetail($config);
    }

    /**
     * {@inheritdoc}
     */
    public function createSelectOption(CreateSelectOptionParameters $parameters): SelectOptionDetail
    {
        $config = $this->selectOptionRepository->create($parameters->getId());

        return $this->hydrateDetail($config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateSelectOption(string $id, UpdateSelectOptionParameters $parameters): SelectOptionDetail
    {
        $config = $this->selectOptionRepository->getById($id);
        $this->validateAdminAccess($config);

        return $this->replaceConfiguration($id, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteSelectOption(string $id): void
    {
        $config = $this->selectOptionRepository->getById($id);
        $this->validateAdminAccess($config);
        $this->selectOptionRepository->delete($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectOptionUsages(string $id): array
    {
        $config = $this->selectOptionRepository->getById($id);
        $fieldsUsedIn = $this->selectOptionRepository->getFieldsUsedIn($config);
        $usages = [];

        foreach ($fieldsUsedIn as $className => $fields) {
            foreach ($fields as $field) {
                $usageItem = new SelectOptionUsageItem($className, $field);
                $this->eventDispatcher->dispatch(
                    new UsageItemEvent($usageItem),
                    UsageItemEvent::EVENT_NAME
                );
                $usages[] = $usageItem;
            }
        }

        return $usages;
    }

    /**
     * {@inheritdoc}
     */
    public function exportSelectOption(string $id): JsonExport
    {
        $config = $this->selectOptionRepository->getById($id);

        try {
            $json = json_encode($config, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $e) {
            throw new ApiInvalidArgumentException(
                'Export data could not be encoded to JSON: ' . $e->getMessage(),
                $e,
            );
        }

        return new JsonExport($json, 'select_option_' . $config->getId() . '_export.json');
    }

    /**
     * {@inheritdoc}
     */
    public function importSelectOptionFromJson(string $id, string $json): SelectOptionDetail
    {
        $config = $this->selectOptionRepository->getById($id);
        $this->validateAdminAccess($config);

        return $this->replaceConfiguration($id, $this->importDataParser->parse($json));
    }

    /**
     * @throws ApiInvalidArgumentException|ElementSavingFailedException|NotWriteableException
     */
    private function replaceConfiguration(string $id, UpdateSelectOptionParameters $parameters): SelectOptionDetail
    {
        $this->validateSelectOptions($parameters->getSelectOptions());

        try {
            $config = $this->configResolver->createFromData([
                Config::PROPERTY_ID => $id,
                Config::PROPERTY_GROUP => $parameters->getGroup(),
                Config::PROPERTY_ADMIN_ONLY => $parameters->isAdminOnly(),
                Config::PROPERTY_USE_TRAITS => $parameters->getUseTraits(),
                Config::PROPERTY_IMPLEMENTS_INTERFACES => $parameters->getImplementsInterfaces(),
                Config::PROPERTY_SELECT_OPTIONS => $parameters->getSelectOptions(),
            ]);
        } catch (InvalidArgumentException | RuntimeException $e) {
            throw new ApiInvalidArgumentException(message: $e->getMessage(), previous: $e);
        }

        $this->selectOptionRepository->save($config);

        return $this->hydrateDetail($config);
    }

    private function hydrateDetail(Config $config): SelectOptionDetail
    {
        $isWriteable = $this->selectOptionRepository->isWriteable($config);
        if (!$this->hasAccess($config)) {
            $isWriteable = false;
        }
        $detail = $this->detailHydrator->hydrate($config, $isWriteable);
        $this->eventDispatcher->dispatch(new DetailEvent($detail), DetailEvent::EVENT_NAME);

        return $detail;
    }

    /**
     * @throws ApiInvalidArgumentException
     */
    private function validateSelectOptions(?array $selectOptions): void
    {
        if ($selectOptions === null) {
            return;
        }

        foreach ($selectOptions as $index => $option) {
            if (!is_array($option) || !isset($option['value']) || $option['value'] === '') {
                throw new ApiInvalidArgumentException(
                    message: sprintf(
                        'Select option at index %d must have a non-empty "value" field',
                        $index,
                    ),
                );
            }
        }
    }

    /**
     * @throws ForbiddenException
     */
    private function validateAdminAccess(Config $config): void
    {
        if ($this->hasAccess($config) === false) {
            throw new ForbiddenException('Restricted to admin users');
        }
    }

    private function hasAccess(Config $config): bool
    {
        return !($config->getAdminOnly() && !$this->securityService->getCurrentUser()->isAdmin());
    }
}
