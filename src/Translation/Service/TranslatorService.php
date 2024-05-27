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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Service;

use InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidLocaleException;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\Translation;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class TranslatorService implements TranslatorServiceInterface
{
    private TranslatorBagInterface $translator;

    public const DOMAIN = 'studio';

    public function __construct(
        TranslatorInterface $translator
    ) {
        if (!$translator instanceof TranslatorBagInterface) {
            throw new InvalidArgumentException('Translator must implement TranslatorBagInterface');
        }

        $this->translator = $translator;
    }

    /**
     * @throws InvalidLocaleException
     */
    public function getAllTranslations(string $locale): Translation
    {
        try {
            $catalogue = $this->translator->getCatalogue($locale)->all(self::DOMAIN);
        } catch (InvalidArgumentException) {
            throw new InvalidLocaleException($locale);
        }

        return new Translation(
            $locale,
            $catalogue
        );
    }

    /**
     * @throws InvalidLocaleException
     */
    public function getTranslationsForKeys(string $locale, array $keys): Translation
    {
        try {
            $catalogue = $this->translator->getCatalogue($locale);
        } catch (InvalidArgumentException) {
            throw new InvalidLocaleException($locale);
        }

        $translations = [];

        foreach ($keys as $key) {
            $translations[$key] = $catalogue->get($key, self::DOMAIN);
        }

        return new Translation($locale, $translations);
    }
}
