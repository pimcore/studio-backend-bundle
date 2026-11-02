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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait;

use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;

/**
 * The one place a classification store key label is read, so that the layout endpoints and the
 * already-active groups of an object layout cannot drift apart on what a key is called.
 *
 * @internal
 */
trait KeyLabelResolverTrait
{
    /**
     * The language a label is read for. The layout payloads carry no language and one of them serves
     * every content language, so the requesting user's own language is the only source available.
     */
    private function resolveLabelLanguage(
        SecurityServiceInterface $securityService,
        ToolResolverInterface $toolResolver
    ): string {
        try {
            return $securityService->getCurrentUser()->getLanguage();
        } catch (UserNotFoundException) {
            // an installation without a default language matches no label row, so the key's own title answers
            return $toolResolver->getDefaultLanguage() ?? '';
        }
    }

    /**
     * Reads the labels of the given keys with a single statement, so that resolving each of them
     * afterwards costs no query.
     *
     * @param int[] $keyIds
     */
    private function preloadKeyLabels(
        LabelResolverInterface $labelResolver,
        array $keyIds,
        string $language
    ): void {
        $labelResolver->preload(LabelEntityType::Key, $keyIds, $language);
    }

    /**
     * The label stored for the key, or null - never its technical name.
     */
    private function resolveStoredKeyTitle(
        KeyConfigResolverInterface $keyConfigResolver,
        LabelResolverInterface $labelResolver,
        int $keyId,
        string $language
    ): ?string {
        $keyConfig = $keyConfigResolver->getById($keyId);

        if ($keyConfig === null) {
            return null;
        }

        return $labelResolver->getStoredTitle($keyConfig, $language);
    }
}
