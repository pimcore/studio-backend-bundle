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
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\PhpCodeTransformerLoaderInterface;
use function get_class;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final class PhpCode implements TransformerInterface
{
    public function __construct(
        private readonly PhpCodeTransformerLoaderInterface $resolver
    ) {
    }

    public function transform(array $value, array $config): array
    {
        $phpClass = $config['phpClass'] ?? null;

        if (!isset($phpClass) || !is_string($phpClass)) {
            throw new TransformerException(
                $this->getName(),
                sprintf(
                    'Invalid "phpClass" configuration (must be a string) for %s transformer. ',
                    $this->getKey()
                )
            );
        }

        //Check if class exists
        $transformer = $this->resolver->resolve($phpClass);

        //Transform the entire value and return result
        return $transformer->transform($value, $config);
    }

    public function getName(): string
    {
        return 'PHP Code';
    }

    public function getKey(): string
    {
        return 'phpCode';
    }

    public function getDescription(): string
    {
        return 'Executes tagged PHP services on grid values.';
    }

    public function getConfigOptions(): array
    {
        $options = [];

        foreach ($this->resolver->getTransformers() as $executable) {
            $options[] = [
                'value' => get_class($executable),
                'label' => $executable->getKey(),
            ];
        }

        return [
            'phpClass' => [
                'type' => 'select',
                'label' => 'PHP Transformer Class',
                'options' => $options,
            ],
            'arguments' => [
                'type' => 'keyValue',
                'label' => 'Arguments',
                'default' => [],
            ],
        ];
    }
}
