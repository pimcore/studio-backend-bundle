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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse;

use OpenApi\Attributes\Schema;
use Symfony\Contracts\EventDispatcher\Event;

final class DocumentTypeSchemasEvent extends Event
{
    public const string EVENT_NAME = 'open_api.document_type_schemas';

    /**
     * @param Schema[] $schemas
     */
    public function __construct(
        private array $schemas
    ) {
    }

    /**
     * Use this to get additional info out of the response object
     *
     * @return Schema[]
     */
    public function getDocumentTypeSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Set the document types to be used in the API response.
     *
     * @param Schema[] $schemas
     */
    public function setDocumentTypeSchemas(array $schemas): void
    {
        $this->schemas = $schemas;
    }
}
