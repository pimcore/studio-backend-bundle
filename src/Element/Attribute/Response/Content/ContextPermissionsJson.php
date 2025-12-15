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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Attribute\Response\Content;

use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\Schemas;

/**
 * @internal
 */
final class ContextPermissionsJson extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            type: 'object',
            example: [
                'add' => true,
                'addFolder' => true,
                'changeChildrenSortBy' => true,
                'copy' => true,
                'cut' => true,
                'delete' => true,
                'lock' => true,
                'lockAndPropagate' => true,
                'paste' => true,
                'publish' => true,
                'refresh' => true,
                'rename' => true,
                'searchAndMove' => true,
                'unlock' => true,
                'unlockAndPropagate' => true,
                'unpublish' => true,
            ],
            additionalProperties: new AdditionalProperties(type: 'boolean')
        );
    }
}
