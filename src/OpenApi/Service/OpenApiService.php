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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Service;

use JsonException;
use OpenApi\Annotations\OpenApi;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use function is_string;

final readonly class OpenApiService implements OpenApiServiceInterface
{
    private const TRANSLATABLE_PROPERTIES = ['summary', 'description'];

    public function __construct(
        private TranslatorServiceInterface $translator,
        private array $openApiScanPaths = []
    ) {
    }

    public function getConfig(): OpenApi
    {
        $config = Generator::scan([...$this->openApiScanPaths]);

        if ($config) {
            usort($config->components->schemas, [$this, 'sortSchemas']);
        }

        return $config;
    }

    public function translateConfig(OpenApi $config): array
    {
        try {

            $configArray = json_decode(
                json_encode(
                    $config,
                    JSON_THROW_ON_ERROR
                ),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw new EnvironmentException('Failed to convert OpenAPI config to array');
        }


        $this->translateRecursive($configArray);

        return $configArray;
    }

    private function sortSchemas(Schema $a, Schema $b): int
    {
        return $a->title <=> $b->title;
    }

    private function translateRecursive(array &$config): void
    {
        foreach ($config as $key => &$value) {
            if (!is_array($value) && is_string($key) && in_array($key, self::TRANSLATABLE_PROPERTIES)) {
                $value = $this->translate($value);
                continue;
            }

            if (is_array($value)) {
                $this->translateRecursive($value); //Recurse into sub-array
            }
        }
    }

    private function translate(string $message): string
    {
        return $this->translator->translateApiDocs($message);
    }
}
