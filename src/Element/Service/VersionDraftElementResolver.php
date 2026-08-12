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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;

/**
 * Pimcore's default: the user's newest unpublished version, if any.
 *
 * @internal
 */
final readonly class VersionDraftElementResolver implements DraftElementResolverInterface
{
    public function resolve(ElementInterface $element, ?UserInterface $user): ElementInterface
    {
        $version = $this->getLatestVersionForUser($element, $user);
        if ($version === null) {
            return $element;
        }

        return $version->getData();
    }

    private function getLatestVersionForUser(ElementInterface $element, ?UserInterface $user): ?Version
    {
        // only these three carry versions; asking anything else is a wasted query
        if (!$element instanceof Asset &&
            !$element instanceof PageSnippet &&
            !$element instanceof Concrete
        ) {
            return null;
        }

        return $element->getLatestVersion($user?->getId());
    }
}
