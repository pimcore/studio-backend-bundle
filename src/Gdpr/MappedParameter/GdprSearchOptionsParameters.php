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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\MappedParameter;

use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
final readonly class GdprSearchOptionsParameters
{
    public function __construct(
        #[Positive]
        public int $page = 1,

        #[Positive]
        public int $pageSize = 20,

        #[Collection(
            fields: [
                'key' => new Type('string'),
                'direction' => new Choice(['ASC', 'DESC', 'asc', 'desc']),
            ],
            allowMissingFields: true
        )]
        public ?array $sortFilter = null
    ) {
    }
}
