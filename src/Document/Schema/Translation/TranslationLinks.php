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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema\Translation;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'DocumentTranslationLinks',
    title: 'Document Translation Links',
    required: ['language'],
    type: 'object'
)]
final class TranslationLinks implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Language', type: 'string', example: 'en')]
        private readonly string $language,
        #[Property(description: 'Translation links', type: 'array', items: new Items(ref: TranslationLink::class))]
        private readonly array $translationLinks = [],
    ) {
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getTranslationLinks(): array
    {
        return $this->translationLinks;
    }
}
