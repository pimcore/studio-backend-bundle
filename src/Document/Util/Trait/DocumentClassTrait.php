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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentTypes;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Email;
use Pimcore\Model\Document\Hardlink;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Snippet;
use Pimcore\Resolver\ResolverInterface;
use function sprintf;

/**
 * @internal
 */
trait DocumentClassTrait
{
    /**
     * @throws InvalidArgumentException
     */
    private function getClassByType(string $type, ResolverInterface $classResolver): string
    {
        return match ($type) {
            DocumentTypes::EMAIL->value => Email::class,
            DocumentTypes::HARDLINK->value => Hardlink::class,
            DocumentTypes::LINK->value => Link::class,
            DocumentTypes::PAGE->value => Page::class,
            DocumentTypes::SNIPPET->value => Snippet::class,
            default => $this->getCustomDocumentClass($type, $classResolver),
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getCustomDocumentClass(string $customType, ResolverInterface $classResolver): string
    {
        $className = $classResolver->resolve($customType);
        if (!is_subclass_of($className, Document::class)) {
            if ($className === null) {
                $className = $customType;
            }

            throw new InvalidArgumentException(
                sprintf("Class '%s' must extend '%s'", $className, Document::class)
            );
        }

        return $className;
    }
}
