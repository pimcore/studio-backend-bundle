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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Repository;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Objectbrick\Definition;

/**
 * @internal
 */
final readonly class ObjectBrickRepository implements ObjectBrickRepositoryInterface
{
    public function __construct(
        private DefinitionResolverInterface $definitionResolver
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listObjectBricks(): array
    {
        return (new Definition\Listing())->load();
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectBrickByKey(string $key): Definition
    {
        $exception = null;
        $definition = null;

        try {
            $definition = $this->definitionResolver->getByKey($key);
        } catch (Exception $e) {
            $exception = $e;
        }
        if (!$definition || $exception) {
            throw new NotFoundException(type: 'Object Brick', id: $key, previous: $exception);
        }

        return $definition;
    }
}
