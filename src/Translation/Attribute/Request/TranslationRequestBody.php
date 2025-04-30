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
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;

#[Attribute(Attribute::TARGET_METHOD)]
final class TranslationRequestBody extends RequestBody
{
    public function __construct(string $content = Translation::class)
    {
        parent::__construct(
            required: true,
            content: new JsonContent(ref: $content)
        );
    }
}
