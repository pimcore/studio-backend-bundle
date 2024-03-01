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

use InvalidArgumentException;
use Pimcore\Bundle\StudioApiBundle\Dto\Translation;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class TranslatorService implements TranslatorServiceInterface
{

    private TranslatorBagInterface $translator;
    private const DOMAIN = 'admin';

    public function __construct(
        TranslatorInterface $translator
    ) {
        if(!$translator instanceof TranslatorBagInterface) {
            throw new InvalidArgumentException('Translator must implement TranslatorBagInterface');
        }

        $this->translator = $translator;
    }

    public function getAllTranslations(string $locale): Translation
    {
        return new Translation(
            $locale,
            $this->translator->getCatalogue($locale)->all(self::DOMAIN)
        );
    }

    public function getTranslationsForKeys(string $locale, array $keys): Translation
    {
        $translations = [];
        foreach ($keys as $key) {
            $translations[$key] = $this->translator->getCatalogue($locale)->get($key, self::DOMAIN);
        }
        return new Translation($locale, $translations);
    }
}