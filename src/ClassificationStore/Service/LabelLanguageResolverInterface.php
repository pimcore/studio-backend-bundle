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

/**
 * @internal
 */
interface LabelLanguageResolverInterface
{
    /**
     * The language a classification store label is resolved for: the requesting user's, and the
     * installation default when there is no current user or the user carries no language.
     */
    public function getLabelLanguage(): string;
}
