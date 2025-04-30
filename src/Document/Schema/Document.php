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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Element;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\CustomAttributesTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\WorkflowAvailableTrait;

#[Schema(
    schema: 'Document',
    title: 'Document',
    required: ['id'],
    type: 'object'
)]
final class Document extends Element implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;
    use CustomAttributesTrait;
    use WorkflowAvailableTrait;

    public function __construct(
        #[Property(description: 'Full path', type: 'string', example: '/path/to/asset.jpg')]
        private readonly string $fullPath,
        int $id,
        int $parentId,
        string $path,
        ElementIcon $icon,
        int $userOwner,
        int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate,
    ) {
        parent::__construct(
            $id,
            $parentId,
            $path,
            $icon,
            $userOwner,
            $userModification,
            $locked,
            $isLocked,
            $creationDate,
            $modificationDate
        );
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }
}
