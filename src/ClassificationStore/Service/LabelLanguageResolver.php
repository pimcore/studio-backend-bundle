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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * The one place the label language is decided, so that the object layout and the classification
 * store layout endpoints cannot drift apart on which language a key is labelled in.
 *
 * A layout response is read by a signed-in editor, but it is also reachable without a resolvable
 * user, so the lookup falls back instead of failing: a label in the installation default language
 * is worth more than an unlabelled payload.
 *
 * @internal
 */
final readonly class LabelLanguageResolver implements LabelLanguageResolverInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private ToolResolverInterface $toolResolver,
    ) {
    }

    public function getLabelLanguage(): string
    {
        try {
            $language = $this->securityService->getCurrentUser()->getLanguage();
        } catch (UserNotFoundException) {
            $language = '';
        }

        if ($language !== '') {
            return $language;
        }

        return $this->toolResolver->getDefaultLanguage() ?? '';
    }
}
