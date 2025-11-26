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

use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\SearchTerms;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Valid;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprSearchOptions;

/**
 * @internal
 */
final readonly class GdprStructuredSearchRequest
{
    /**
     * @param string[] $providers
     */
    public function __construct(
        #[NotBlank]
        #[All(new Type('string'))]
        public array $providers,

        #[Valid]
        #[NotNull]
        public SearchTerms $searchTerms,

        #[Valid]
        public GdprSearchOptions $filters
    ) {
    }
}
