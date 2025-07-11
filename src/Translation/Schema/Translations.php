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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PublicTranslations;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'Translations',
    title: 'Translations',
    description: 'Translations including all languages and keys',
    required: ['key', 'translations', 'type'],
    type: 'object'
)]
final class Translations implements AdditionalAttributesInterface
{

    use AdditionalAttributesTrait;
    public function __construct(
        #[Property(description: 'Key of the translation', type: 'string', example: 'car')]
        private readonly string $key,
        #[Property(
            description: 'List of translations for the given key',
            type: 'array',
            items: new Items(
                type: 'object',
                example: ['en' => 'Car', 'de' => 'Auto', 'fr' => 'Voiture']
            ))]
        private readonly array $translations,
        #[Property(
            description: 'Type simple or custom',
            type: 'string',
            example: 'simple'
        )]
        private readonly string $type
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
