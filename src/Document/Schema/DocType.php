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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'DocType',
    title: 'DocType',
    required: [
        'id', 'name', 'type', 'group', 'controller', 'template', 'priority',
        'creationDate', 'modificationDate', 'staticGeneratorEnabled', 'writeable'
    ],
    type: 'object'
)]
final class DocType implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'string', example: '1')]
        private readonly string $id,
        #[Property(description: 'Name', type: 'string', example: 'Default Page')]
        private readonly string $name,
        #[Property(description: 'Type', type: 'string', example: DocumentTypes::PAGE->value)]
        private readonly string $type,
        #[Property(description: 'Group', type: 'string', example: 'Default')]
        private readonly ?string $group = null,
        #[Property(
            description: 'Controller',
            type: 'string',
            example: 'App\\Controller\\DefaultController::indexAction'
        )]
        private readonly ?string $controller = null,
        #[Property(description: 'Template', type: 'string', example: '@App/Resources/views/default.html.twig')]
        private readonly ?string $template = null,
        #[Property(description: 'Priority', type: 'integer', example: 0)]
        private readonly int $priority = 0,
        #[Property(description: 'Creation date', type: 'integer', example: null)]
        private readonly ?int $creationDate = null,
        #[Property(description: 'Modification date', type: 'integer', example: null)]
        private readonly ?int $modificationDate = null,
        #[Property(description: 'Static generator enabled', type: 'boolean', example: false)]
        private readonly bool $staticGeneratorEnabled = false,
        #[Property(description: 'Is writeable', type: 'boolean', example: false)]
        private readonly bool $writeable = false,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getController(): ?string
    {
        return $this->controller;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function isStaticGeneratorEnabled(): bool
    {
        return $this->staticGeneratorEnabled;
    }

    public function isWriteable(): bool
    {
        return $this->writeable;
    }
}
