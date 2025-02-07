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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Repository;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Loader\Widget\TaggedIteratorRepository;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\WidgetTypes;
use Pimcore\Config\LocationAwareConfigRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(TaggedIteratorRepository::REPOSITORY_TAG)]
final class ElementTreeWidgetConfigRepository implements WidgetConfigRepositoryInterface
{
    public function __construct(
        private readonly array $widgetConfigurations,
        private readonly array $storageConfig,
    ) {
    }

    private ?LocationAwareConfigRepository $repository = null;

    public function getSupportedWidgetType(): string
    {
        return WidgetTypes::ELEMENT_TREE->value;
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function getConfigData(string $widgetId): array
    {
        $repository = $this->getRepository();
        $data = $repository->loadConfigByKey($widgetId);
        [$configData, $dataSource] = $data;
        if ($configData === null) {
            throw new NotFoundException('Element Tree Widget', $widgetId);
        }

        try {
            $configData['isWriteable'] = $repository->isWriteable($widgetId, $dataSource);
        } catch (Exception $exception) {
            throw new NotWriteableException('Widget configuration export', $exception);
        }
        $configData['id'] = $widgetId;

        return $configData;
    }

    private function getRepository(): LocationAwareConfigRepository
    {
        if (!$this->repository) {
            $this->repository = new LocationAwareConfigRepository(
                $this->widgetConfigurations,
                Configuration::TREE_WIDGETS_NODE,
                $this->storageConfig
            );
        }

        return $this->repository;
    }
}
