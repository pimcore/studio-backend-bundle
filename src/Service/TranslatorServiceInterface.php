<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StudioApiBundle\Dto\Translation;;

/**
 * @internal
 */
interface TranslatorServiceInterface
{
    public function getAllTranslations(string $locale): Translation;
    public function getTranslationsForKeys(string $locale, array $keys): Translation;

}