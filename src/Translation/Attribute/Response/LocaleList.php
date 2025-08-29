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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Attribute\Response;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;

/**
 * @internal
 */
final class LocaleList extends JsonContent
{
    public function __construct()
    {
        parent::__construct(
            type: 'array',
            items: new Items(
                required: ['local', 'displayName'],
                properties: [
                    new Property(
                        'locale',
                        title: 'locale',
                        description: 'Locale code.',
                        type: 'string',
                        example: 'de_de'
                    ),
                    new Property(
                        'displayName',
                        title: 'Display Name',
                        description: 'The display name of the locale.',
                        type: 'string',
                        example: 'Deutsch (Deutschland)'
                    ),
                ],
                type: 'object'
            )
        );
    }
}
