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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Request\Grid;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property\SaveConfigurationColumn;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\ListOfInteger;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleBoolean;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleInteger;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleString;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class SaveConfigurationRequestBody extends RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['folderId'],
                properties: [
                    new SingleInteger('folderId'),
                    new SingleInteger('pageSize'),
                    new SingleString('name'),
                    new SingleString('description'),
                    new SingleBoolean('shareGlobal'),
                    new SingleBoolean('setAsFavorite'),
                    new SingleBoolean('saveFilter'),
                    new ListOfInteger('sharedUsers'),
                    new ListOfInteger('sharedRoles'),
                    new SaveConfigurationColumn(),
                    new Property(
                        'filter',
                        ref: Filter::class,
                        type: 'object',
                        nullable: true,
                    ),
                ],
                type: 'object',
            ),
        );
    }
}
