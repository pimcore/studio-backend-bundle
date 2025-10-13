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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Transformer;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Symfony\Contracts\Translation\TranslatorInterface;

use function is_string;
use function sprintf;

/**
 * @internal
 */
final readonly class Translate implements TransformerInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function transform(array $value, array $config): array
    {
        if (isset($config['prefix']) && !is_string($config['prefix'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf(
                    'Invalid "prefix" configuration (must be a string) for %s transformer.',
                    $this->getKey()
                )
            );
        }

        if (isset($config['locale']) && !is_string($config['locale'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf(
                    'Invalid "locale" configuration (must be a string) for %s transformer.',
                    $this->getKey()
                )
            );
        }

        $prefix = $config['prefix'] ?? '';
        $locale = $config['locale'] ?? null;

        $translatedValues = [];

        foreach ($value as $item) {
            if (!$item instanceof AdvancedValue) {
                continue;
            }

            $originalValue = (string) $item->getValue();
            if ($originalValue === '') {
                $translatedValues[] = $item;

                continue;
            }

            $translated = $this->translator->trans(
                $prefix . $originalValue,
                [],
                null,
                $locale
            );

            $translatedValues[] = new AdvancedValue('string', $translated, $item->getFieldName());
        }

        return $translatedValues;
    }

    public function getName(): string
    {
        return 'Translate';
    }

    public function getKey(): string
    {
        return 'translate';
    }

    public function getDescription(): string
    {
        return 'Translates the value using Symfony Translator. You can optionally add a prefix and set a locale.';
    }

    public function getConfigOptions(): array
    {
        return [
            'prefix' => [
                'type' => 'text',
                'default' => '',
                'label' => 'Translation Prefix',
               'description' => 'Prefix added before the value for translation. '
               . 'Example: "attribute." → "attribute.myValue".',
            ],
        ];
    }
}
