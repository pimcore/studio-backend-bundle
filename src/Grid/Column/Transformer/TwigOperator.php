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

use DateTimeInterface;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Pimcore\Bundle\StudioBackendBundle\Twig\TemplateGeneratorInterface;
use function array_map;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

/**
 * @internal
 */
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
     * Recursively strips every value down to plain data (scalars, arrays, null) before it
     * reaches the Twig sandbox - regardless of how well the sandbox policy is locked down,
     * an object reaching the template can expose whatever methods/properties are reachable
     * on it. This is what closes the exploit reported against
     * {@see \Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\DateRangeAdapter}: its
     * `normalize()` returns raw `Carbon\Carbon` instances (a `DateTime` subclass with dozens
     * of methods, including `macro()`, which registers an arbitrary PHP callable - even a
     * global function name - as a callable method), which the security-policy denylist did
     * not, and structurally cannot exhaustively, cover.
     *
     * - Arrays are walked recursively, keys preserved.
     * - `DateTimeInterface` (covers `DateTime`, `DateTimeImmutable`, and Carbon's subclasses
     *   of both) is converted to an ISO 8601 string. This is deliberately narrow: it is the
     *   one object type known to legitimately reach this context today (date/date-range
     *   columns). The `date`/`date_modify`/`format_date` filters all accept a string in this
     *   format the same way they accept a DateTime instance, so formatting keeps working.
     * - Any other object is dropped (replaced with null) rather than string-cast: casting
     *   would silently invoke `__toString()` on whatever reaches this method next, which is
     *   exactly the kind of implicit method call this sanitizer exists to avoid. There is no
     *   other object type this transformer has a legitimate use for; if one is ever needed,
     *   it should be added here explicitly, converted to its own plain-data representation.
     * - Scalars and null pass through unchanged.
     */
    private function sanitizeForTemplate(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map($this->sanitizeForTemplate(...), $value);
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if (is_object($value)) {
            return null;
        }

        // Only scalars and null remain at this point.
        return $value;
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
