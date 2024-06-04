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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter;

/**
 * @internal
 */
final readonly class ElementParameters
{
    public function __construct(
        private ?string $type = null,
        private ?int $id = null
    )
    {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
