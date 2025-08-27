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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\UpdateBooleanProperty;
use Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Property\CsvSettingsProperty;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ImportRequestBody extends RequestBody
{
    public function __construct() {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['csvSettings'],
                properties: [
                    new CsvSettingsProperty()
                ],
                type: 'object',
            ),
        );
    }
}
