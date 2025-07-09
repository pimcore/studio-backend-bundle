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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Request;

use Attribute;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Property\SaveConfigurationColumn as AssetSaveConfigurationColumn;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Attribute\Property\SaveConfigurationColumn as DataObjectSaveConfigurationColumn;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Filter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\ListOfInteger;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleBoolean;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleInteger;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\SingleString;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ConfigurationRequestBody extends RequestBody
{
    public function __construct(
        string $type
    )
    {
        match ($type) {
            'data_object' => $column = new DataObjectSaveConfigurationColumn(),
            'asset' => $column = new AssetSaveConfigurationColumn(),
            default => throw new InvalidArgumentException('Invalid type provided for ConfigurationRequestBody'),
        };

        parent::__construct(
            required: true,
            content: new JsonContent(
                required: ['folderId', 'pageSize', 'name', 'description', 'columns'],
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
                    $column,
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
