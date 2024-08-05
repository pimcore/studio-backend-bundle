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

use OpenApi\Annotations\OpenApi;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use ReflectionClass;
use function PHPUnit\Framework\isInstanceOf;
use function Sabre\Event\Loop\instance;

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
        $configArray = json_decode(
            json_encode(
                $config,
                JSON_THROW_ON_ERROR
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->translateRecursive($configArray);

        return $configArray;
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

    private function sortSchemas(Schema $a, Schema $b): int
    {
        return $a->title <=> $b->title;
    }


  // private function translatePathProperties(OpenApi $config): void
  // {
  //     $responses = [];
  //     foreach ($config->paths as $path) {
  //         foreach (self::TRANSLATABLE_CRUD_METHODS as $method) {
  //             if (is_string($path->{$method})) {
  //                 continue;
  //             }

  //             $responses = [...$responses, ...$path->{$method}->responses];

  //             foreach (self::TRANSLATABLE_PATH_PROPERTIES as $property) {
  //                 if ((string)$path->{$method}->{$property} === Generator::UNDEFINED) {
  //                     continue;
  //                 }
  //                 $path->{$method}->{$property} = $this->translate((string)$path->{$method}->{$property});
  //             }
  //         }
  //     }
  //     $this->translateResponseDescriptions($responses);
  // }

  // private function translateSchemaDescriptions(OpenApi $config): void
  // {
  //     foreach ($config->components->schemas as $schema) {
  //         if ($schema->description === Generator::UNDEFINED) {
  //             continue;
  //         }
  //         $schema->description = $this->translate($schema->description);
  //     }
  // }

  // private function translateTagsProperties(OpenApi $config): void
  // {
  //     foreach ($config->tags as $tag) {
  //         foreach (self::TRANSLATABLE_TAG_PROPERTIES as $property) {
  //             if ((string)$tag->{$property} === Generator::UNDEFINED) {
  //                 continue;
  //             }
  //             $tag->{$property} = $this->translate((string)$tag->{$property});
  //         }
  //     }
  // }

  // private function translateResponseDescriptions(array $responses): void
  // {
  //     foreach ($responses as $response) {
  //         if ($response->description === Generator::UNDEFINED) {
  //             continue;
  //         }

  //         $response->description = $this->translate($response->description);
  //     }
  // }

   private function translate(string $message): string
   {
       return $this->translator->translateApiDocs($message);
   }
}
