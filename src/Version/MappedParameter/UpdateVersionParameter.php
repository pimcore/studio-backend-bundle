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

namespace Pimcore\Bundle\StudioBackendBundle\Version\MappedParameter;

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @internal
 */
final readonly class UpdateVersionParameter
{
    public function __construct(
        private ?bool $public = null,
        private ?string $note = null,
        #[Length(max: VersionCoauthor::MAX_TYPE_LENGTH)]
        private ?string $coauthorType = null,
        #[Length(max: VersionCoauthor::MAX_COAUTHOR_LENGTH)]
        private ?string $coauthor = null,
    ) {
    }

    public function isPublic(): ?bool
    {
        return $this->public;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getCoauthorType(): ?string
    {
        return $this->coauthorType;
    }

    public function getCoauthor(): ?string
    {
        return $this->coauthor;
    }
}
