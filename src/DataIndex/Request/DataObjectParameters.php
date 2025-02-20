<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
     * @return string[]|null
     */
    public function getClassIdsArray(): ?array
    {
        return $this->classIdsArray;
    }
}
