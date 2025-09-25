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
use Pimcore\Bundle\StudioBackendBundle\Twig\TemplateGeneratorInterface;

final class TwigOperator implements TransformerInterface
{
    public function __construct(
        private readonly TemplateGeneratorInterface $templateGenerator
    ) {}

    public function transform(array $value, array $config): array
    {
        $template = $config['template'] ?? '{{ value }}';

        foreach ($value as $val) {
            try {
                $rendered = $this->templateGenerator->generate($template, ['value' => $val->getValue()]);
                $val->setValue($rendered);
            } catch (\Throwable $e) {
                throw new TransformerException(
                    $this->getName(),
                    sprintf(
                        'Twig rendering failed for %s transformer: %s',
                        $this->getKey(),
                        $e->getMessage()
                    )
                );

            }
        }

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
                'description' => 'Write a Twig template using {{ value }} as the placeholder.',
            ],
        ];
    }
}