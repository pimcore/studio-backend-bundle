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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @internal
 */
final readonly class KeyCollectionParameters extends CollectionFilterParameter
{
    public function __construct(
        ?FilterParameter $filters = null,
        private bool $disabledOnly = false,
        // A group id, not a list of key ids: what the group holds is read when the page is read
        #[Positive(message: 'The group to exclude the assigned keys of must be a positive id.')]
        private ?int $excludeKeysOfGroupId = null,
    ) {
        parent::__construct($filters);
    }

    public function isDisabledOnly(): bool
    {
        return $this->disabledOnly;
    }

    public function getExcludeKeysOfGroupId(): ?int
    {
        return $this->excludeKeysOfGroupId;
    }
}
