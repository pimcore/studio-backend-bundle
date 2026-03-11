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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Request;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ClassIdsParameterInterface;

/**
 * @internal
 */
final readonly class DataObjectParameters extends ElementParameters implements
    ClassIdsParameterInterface,
    ClassNameParametersInterface
{
    private array $classIdsArray;

    /**
     * @throws JsonException
     */
    public function __construct(
        int $page = 1,
        int $pageSize = 10,
        ?int $parentId = null,
        ?string $idSearchTerm = null,
        ?string $pqlQuery = null,
        bool $excludeFolders = false,
        ?string $path = null,
        bool $pathIncludeParent = false,
        bool $pathIncludeDescendants = false,
        private ?string $className = null,
        ?string $classIds = null
    ) {
        $this->classIdsArray = $classIds !== null ?
            json_decode($classIds, true, 512, JSON_THROW_ON_ERROR) :
            [];

        parent::__construct(
            $page,
            $pageSize,
            $parentId,
            $idSearchTerm,
            $pqlQuery,
            $excludeFolders,
            $path,
            $pathIncludeParent,
            $pathIncludeDescendants
        );
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * @return string[]
     */
    public function getClassIdsArray(): array
    {
        return $this->classIdsArray;
    }
}
