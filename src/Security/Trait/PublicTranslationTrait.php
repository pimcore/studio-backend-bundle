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

namespace Pimcore\Bundle\StudioApiBundle\Security\Trait;

use Pimcore\Bundle\StudioApiBundle\Exception\NonPublicTranslationException;
use Pimcore\Bundle\StudioApiBundle\Util\Constants\PublicTranslations;
use Symfony\Component\HttpFoundation\InputBag;

trait PublicTranslationTrait
{
    private const ARRAY_KEYS_INDEX = 'keys';

    private function voteOnTranslation(InputBag $payload): bool
    {
        $parameters = $payload->all();
        if (!array_key_exists(self::ARRAY_KEYS_INDEX, $parameters)) {
            return false;
        }

        $nonPublicTranslations = array_diff($parameters[self::ARRAY_KEYS_INDEX], PublicTranslations::PUBLIC_KEYS);

        if (!empty($nonPublicTranslations)) {
            throw new NonPublicTranslationException(sprintf('You have requested non public keys: %s', implode(',', $nonPublicTranslations)));
        }

        return true;
    }
}
