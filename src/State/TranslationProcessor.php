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

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Translation;
use Pimcore\Bundle\StudioApiBundle\Service\TranslatorServiceInterface;

/**
 * @internal
 */
final class TranslationProcessor implements ProcessorInterface
{
    private const OPERATION_URI_TEMPLATE = '/translations';

    public function __construct(
        private readonly TranslatorServiceInterface $translatorService
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Translation
    {
         if (
             !$operation instanceof Post ||
             !$data instanceof Translation ||
             $operation->getUriTemplate() !== self::OPERATION_URI_TEMPLATE
         ) {
             // wrong operation
             throw new OperationNotFoundException();
         }


        if(empty($data->getKeys())) {
            return $this->translatorService->getAllTranslations($data->getLocale());
        }

        return $this->translatorService->getTranslationsForKeys($data->getLocale(), $data->getKeys());
    }
}