<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 * @license    Pimcore Open Core License (POCL)
 */


namespace Pimcore\Bundle\StudioBackendBundle\Translation\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translations;
use Pimcore\Model\Translation;

/**
 * @internal
 */
final class TranslationsHydrator implements TranslationsHydratorInterface
{
    public function hydrate(Translation $translation): Translations
    {
        return new Translations(
            $translation->getKey(),
            $translation->getTranslations(),
            $translation->getType()
        );
    }
}