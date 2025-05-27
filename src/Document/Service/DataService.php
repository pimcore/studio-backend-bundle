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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\Document as DocumentModel;
use Pimcore\Model\Version;

/**
 * @internal
 */
final readonly class DataService implements DataServiceInterface
{
    public function __construct(
        private WorkflowDetailsServiceInterface $workflowDetailsService,
    ) {
    }

    public function setDocumentDetailData(
        Document $document,
        DocumentModel $element,
        ?Version $documentVersion = null,
    ): void {
        $document->setHasWorkflowAvailable($this->workflowDetailsService->hasElementWorkflows($element));
    }

    public function updateDocumentData(
        DocumentModel $document,
        array $data
    ): void
    {
        //ToDo: Add adapters to process specific data based on the type of the document
    }
}
