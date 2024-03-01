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

namespace Pimcore\Bundle\StudioApiBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Pimcore\Bundle\StudioApiBundle\Dto\Translation;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class TranslationProcessor implements ProcessorInterface
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

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Translation
    {

      // if (
      //     !$operation instanceof Post ||
      //     !$data instanceof Translation ||
      //     $operation->getUriTemplate() !== '/translations'
      // ) {
      //     // wrong operation
      //     throw new OperationNotFoundException();
      // }

        $translationKeys = $data->getTranslationKeys();

        if(empty($translationKeys)) {
            return $this->getAllTranslations($data->getLocale());
        }

        return $this->getTranslations($data->getLocale(), $translationKeys);
    }

    private function getAllTranslations(string $locale): Translation
    {
        return new Translation(
            $locale,
            $this->translator->getCatalogue($locale)->all(self::DOMAIN)
        );
    }

    private function getTranslations(string $locale, array $translationKeys): Translation
    {
        $translations = [];
        foreach ($translationKeys as $translationKey) {
            $translations[$translationKey] = $this->translator->getCatalogue($locale)->get($translationKey, self::DOMAIN);
        }
        return new Translation($locale, $translations);
    }
}