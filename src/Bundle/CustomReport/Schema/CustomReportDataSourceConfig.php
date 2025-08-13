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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleCustomReportsDataSourceConfig',
    title: 'Bundle Custom Reports Data Source Config',
    required: ['configuration'],
    type: 'object',
)]
final readonly class CustomReportDataSourceConfig {

    public function __construct(
        #[Property(
            description: 'Configuration for data source. Content of array depends on selected adapter/data source',
            type: 'object',
            example: [
                'sql' => 'carClass',
                'from' => 'object_localized_CAR_en',
                'where' => "objectType = 'actual-car'",
                'groupby' => 'carClass',
                'orderby' => '',
                'orderbydir' => null,
                'type' => 'sql',
            ]
        )]
        private array $configuration = []
    ) {

    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
