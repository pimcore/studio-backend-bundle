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

use BackedEnum;
use DateTimeInterface;
use Exception;
use JsonSerializable;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\ConsentData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Pimcore\Bundle\StudioBackendBundle\Twig\TemplateGeneratorInterface;
use UnitEnum;
use function array_map;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

final class TwigOperator implements TransformerInterface
{
    private const int MAX_DEPTH = 32;

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

            $value = $this->sanitizeForTemplate($item->getValue());

            if ($item->getRelation() !== null) {
                $assoc[$item->getRelation()][$item->getFieldName()] = $value;

                continue;
            }

            $assoc[$item->getFieldName()] = $value;
        }

        return $assoc;
    }

    /**
     * Reduces a value to plain data (scalars, arrays, null) before it reaches the Twig sandbox, so no object
     * method or property is reachable from a template:
     * - arrays are walked recursively, keys preserved, up to {@see self::MAX_DEPTH} levels;
     * - dates become ISO 8601 strings, which the `date` filters accept like a date object;
     * - consent values, `JsonSerializable` objects and enums become their data;
     * - any other object becomes null; it is never string-cast, which would call `__toString()`.
     */
    private function sanitizeForTemplate(mixed $value, int $depth = 0): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            return null;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->sanitizeForTemplate($item, $depth + 1), $value);
        }

        return match (true) {
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof ConsentData => [
                'consent' => $value->getConsent(),
                'noteId' => $value->getNoteId(),
                'noteContent' => $value->getNoteContent(),
            ],
            $value instanceof JsonSerializable => $this->sanitizeForTemplate($value->jsonSerialize(), $depth + 1),
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            is_object($value) => null,
            default => $value,
        };
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
