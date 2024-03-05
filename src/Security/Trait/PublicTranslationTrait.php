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

namespace Pimcore\Bundle\StudioApiBundle\Security\Trait;

use Pimcore\Bundle\StudioApiBundle\Util\Constants\PublicTranslations;
use Symfony\Component\HttpFoundation\InputBag;

trait PublicTranslationTrait
{
    private const ARRAY_KEYS_INDEX = 'keys';
    private function voteOnTranslation(InputBag $payload): bool
    {
        $parameters = $payload->all();
        if(!array_key_exists(self::ARRAY_KEYS_INDEX, $parameters)) {
            return false;
        }

        foreach($parameters[self::ARRAY_KEYS_INDEX] as $key) {
            // Allow only public keys
            if(!in_array($key, PublicTranslations::PUBLIC_KEYS, true)) {
                return false;
            }
        }

        return true;
    }
}