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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property;

use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;

/**
 * @internal
 */
final class CoauthorTypeProperty extends Property
{
    public function __construct()
    {
        parent::__construct(
            property: ElementSaveServiceInterface::INDEX_COAUTHOR_TYPE,
            description: 'Optional coauthor type stored on versions created by this save',
            type: 'string',
            maxLength: VersionCoauthor::MAX_TYPE_LENGTH,
            example: 'agent',
        );
    }
}
