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
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_is_list;
use function array_key_exists;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function sprintf;

/**
 * @internal
 */
final readonly class SelectOptionService implements SelectOptionServiceInterface
{
    private const array IMPORT_OPTION_FIELDS = [
        SelectOption::PROPERTY_VALUE,
        SelectOption::PROPERTY_LABEL,
        SelectOption::PROPERTY_NAME,
    ];

    public function __construct(
        private SelectOptionRepositoryInterface $selectOptionRepository,
        private ConfigResolverInterface $configResolver,
        private DetailHydratorInterface $detailHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
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

        return $this->replaceConfiguration($id, $this->createImportParameters($json));
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
     * @throws ApiInvalidArgumentException
     */
    private function createImportParameters(string $json): UpdateSelectOptionParameters
    {
        $data = $this->decodeImportData($json);
        $group = $data[Config::PROPERTY_GROUP] ?? null;
        $adminOnly = $data[Config::PROPERTY_ADMIN_ONLY] ?? false;
        $useTraits = $data[Config::PROPERTY_USE_TRAITS] ?? '';
        $implementsInterfaces = $data[Config::PROPERTY_IMPLEMENTS_INTERFACES] ?? '';
        $selectOptions = $data[Config::PROPERTY_SELECT_OPTIONS];

        $this->assertImportType(Config::PROPERTY_GROUP, $group, ['string', 'NULL']);
        $this->assertImportType(Config::PROPERTY_ADMIN_ONLY, $adminOnly, ['boolean']);
        $this->assertImportType(Config::PROPERTY_USE_TRAITS, $useTraits, ['string']);
        $this->assertImportType(Config::PROPERTY_IMPLEMENTS_INTERFACES, $implementsInterfaces, ['string']);
        $this->validateImportSelectOptions($selectOptions);

        return new UpdateSelectOptionParameters(
            $group,
            $adminOnly,
            $useTraits,
            $implementsInterfaces,
            $selectOptions
        );
    }

    /**
     * @throws ApiInvalidArgumentException
     *
     * @return array<string, mixed>
     */
    private function decodeImportData(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiInvalidArgumentException('Uploaded file is not valid JSON: ' . $e->getMessage(), $e);
        }

        if (!is_array($data) || !array_key_exists(Config::PROPERTY_SELECT_OPTIONS, $data)) {
            throw new ApiInvalidArgumentException(
                'Uploaded file is not a select options export: missing "selectOptions".'
            );
        }

        return $data;
    }

    /**
     * @throws ApiInvalidArgumentException
     */
    private function validateImportSelectOptions(mixed $selectOptions): void
    {
        if (!is_array($selectOptions) || !array_is_list($selectOptions)) {
            throw new ApiInvalidArgumentException(
                sprintf('Invalid value for "%s": expected a list of objects.', Config::PROPERTY_SELECT_OPTIONS)
            );
        }

        foreach ($selectOptions as $index => $option) {
            $path = sprintf('%s[%d]', Config::PROPERTY_SELECT_OPTIONS, $index);
            $this->assertImportType($path, $option, ['array']);

            foreach (self::IMPORT_OPTION_FIELDS as $field) {
                $this->assertImportType($path . '.' . $field, $option[$field] ?? '', ['string']);
            }
        }
    }

    /**
     * @param string[] $allowedTypes
     *
     * @throws ApiInvalidArgumentException
     */
    private function assertImportType(string $path, mixed $value, array $allowedTypes): void
    {
        $type = gettype($value);

        if (!in_array($type, $allowedTypes, true)) {
            throw new ApiInvalidArgumentException(
                sprintf('Invalid value for "%s": expected %s, got %s.', $path, implode(' or ', $allowedTypes), $type)
            );
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
