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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\ApplicationLoggerBundle\Service\TranslationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;

/**
 * @internal
 */
#[Schema(
    schema: 'TranslationData',
    title: 'Translation Data',
    description: 'Translation Data Scheme for API',
    required: ['key', 'translation', 'type'],
    type: 'object'
)]
final readonly class TranslationData
{
    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'my_translation_key')]
        private string $key,
        #[Property(description: 'Translation', type: 'string', example: 'some_translated_string')]
        private string $translation,
        #[Property(description: 'Type', type: 'string', example: 'simple')]
        private string $type = 'simple',
        #[Property(description: 'Domain', type: 'domain', example: 'studio')]
        private string $domain = TranslatorServiceInterface::DOMAIN
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTranslation(): string
    {
        return $this->translation;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
