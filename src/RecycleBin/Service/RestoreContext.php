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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_values;

/**
 * @internal
 */
final class RestoreContext implements RestoreContextInterface
{
    use ElementProviderTrait;

    private ?UserInterface $user = null;

    /**
     * @var array<string, ElementInterface>
     */
    private array $relocatedElements = [];

    public function start(UserInterface $user): void
    {
        $this->user = $user;
        $this->relocatedElements = [];
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function addRelocatedElement(ElementInterface $element): void
    {
        // the data object restore saves a placeholder with the same ID first, the last save wins
        $this->relocatedElements[$this->getElementType($element) . '_' . $element->getId()] = $element;
    }

    public function getRelocatedElements(): array
    {
        return array_values($this->relocatedElements);
    }

    public function clear(): void
    {
        $this->user = null;
        $this->relocatedElements = [];
    }
}
