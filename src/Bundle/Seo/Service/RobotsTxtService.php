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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\SettingsStoreResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Event\PreResponse\RobotsTxtConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator\RobotsTxtHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RobotsTxtConfig;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RobotsTxtUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function file_exists;

/**
 * @internal
 */
final readonly class RobotsTxtService implements RobotsTxtServiceInterface
{
    private const string SETTINGS_STORE_SCOPE = 'robots.txt';

    public function __construct(
        private SettingsStoreResolverInterface $settingsStoreResolver,
        private RobotsTxtHydratorInterface $hydrator,
        private EventDispatcherInterface $eventDispatcher,
        private string $projectDir,
    ) {
    }

    public function getRobotsTxtConfig(): RobotsTxtConfig
    {
        $data = [];
        $ids = $this->settingsStoreResolver->getIdsByScope(self::SETTINGS_STORE_SCOPE);

        foreach ($ids as $id) {
            $settingsEntry = $this->settingsStoreResolver->get($id, self::SETTINGS_STORE_SCOPE);
            if ($settingsEntry === null) {
                continue;
            }

            $siteId = preg_replace('/^robots\.txt\-/', '', $settingsEntry->getId());
            $data[$siteId] = $settingsEntry->getData();
        }

        $onFileSystem = file_exists($this->projectDir . '/public/robots.txt');

        $config = $this->hydrator->hydrateRobotsTxtConfig($data, $onFileSystem);
        $this->eventDispatcher->dispatch(
            new RobotsTxtConfigEvent($config),
            RobotsTxtConfigEvent::EVENT_NAME
        );

        return $config;
    }

    public function updateRobotsTxtConfig(RobotsTxtUpdateParameters $parameters): RobotsTxtConfig
    {
        try {
            foreach ($parameters->getData() as $siteConfig) {
                $this->settingsStoreResolver->set(
                    'robots.txt-' . $siteConfig->getSiteId(),
                    $siteConfig->getContent(),
                    'string',
                    self::SETTINGS_STORE_SCOPE
                );
            }
        } catch (Exception $e) {
            throw new EnvironmentException(
                $e->getMessage(),
                previous: $e
            );
        }

        return $this->getRobotsTxtConfig();
    }
}
