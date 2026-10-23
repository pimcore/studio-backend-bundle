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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Pimcore\Bundle\StudioBackendBundle\Twig\TemplateGeneratorInterface;
use function is_string;
use function sprintf;

final class TwigOperator implements TransformerInterface
{
    public function __construct(
        private readonly TemplateGeneratorInterface $templateGenerator
    ) {
    }

    public function transform(array $value, array $config): array
    {
        // Validate template configuration
        if (isset($config['template']) && !is_string($config['template'])) {
            throw new TransformerException(
                $this->getName(),
                sprintf(
                    'Invalid "template" configuration (must be a string) for %s transformer. ' .
                    'Example: "template": "{{ value|date(\'d.m.Y H:i\') }}"',
                    $this->getKey()
                )
            );
        }

        $template = $config['template'] ?? '{{ value }}';

        $context = [
            'value' => $this->buildAssociativeContext($value),
        ];

        try {
            $rendered = $this->templateGenerator->generate($template, $context);
        } catch (Exception $e) {
            throw new TransformerException(
                $this->getName(),
                sprintf('Failed to render Twig template: %s', $e->getMessage())
            );
        }

        $fieldName = $config['columnKey'] ?? $this->getKey();

        return [
            new AdvancedValue('string', $rendered, $fieldName),
        ];
    }

    /**
     * Ensures that the template receives plain data (e.g., strings, arrays) instead of wrapped objects.
     */
    private function buildAssociativeContext(array $values): array
    {
        $assoc = [];

        foreach ($values as $item) {
            if (!$item instanceof AdvancedValue || !$item->getFieldName()) {
                continue;
            }

            if ($item->getRelation() !== null) {
                $assoc[$item->getRelation()][$item->getFieldName()] = $item->getValue();

                continue;
            }

            $assoc[$item->getFieldName()] = $item->getValue();
        }

        return $assoc;
    }

    public function getName(): string
    {
        return 'Twig Operator';
    }

    public function getKey(): string
    {
        return 'twigOperator';
    }

    public function getDescription(): string
    {
        return 'Applies a Twig template to the value. You can use {{ value }} and Twig filters.';
    }

    public function getConfigOptions(): array
    {
        return [
            'template' => [
                'type' => 'code',
                'language' => 'twig',
                'default' => '{{ value }}',
                'label' => 'Twig Template',
                'description' => 'Write a Twig template using {{ value }} as the placeholder. '
                    . 'If advanced columns are configured, you can access them by their field names '
                    . '(e.g., {{ value.someField }}).',
            ],
        ];
    }
}
