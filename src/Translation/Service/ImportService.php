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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Hydrator\DeltaItemHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\MappedParameter\CsvSettingsParameter;
use Pimcore\Model\Translation;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
final readonly class ImportService implements ImportServiceInterface
{
    public function __construct(
        private DeltaItemHydratorInterface $hydrator,
        private LanguageServiceInterface $languageService,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function import(UploadedFile $file, string $domain, CsvSettingsParameter $parameter): array
    {
        $user = $this->securityService->getCurrentUser();
        $this->languageService->validateAdminPermission($user, $domain);

        $allowedLanguages = $this->languageService->getTranslationAllowedLanguages($user, $domain);
        try {
            $delta = Translation::importTranslationsFromFile(
                $file->getPathname(),
                $domain,
                false,
                $allowedLanguages,
                $this->getDialect($parameter)
            );
        } catch (Exception $e) {
            throw new EnvironmentException(
                sprintf('Failed to import translations from CSV file: %s', $e->getMessage())
            );
        }

        $result = [];
        foreach ($this->groupDeltaItems($delta) as $key => $items) {
            $result[] = $this->hydrator->hydrate($key, $items);
        }

        return $result;
    }
    private function groupDeltaItems(array $delta): array
    {
        $grouped = [];
        foreach ($delta as $item) {
            $grouped[$item['key']][] = $item;
        }

        return $grouped;
    }

    private function getDialect(CsvSettingsParameter $parameter): stdClass
    {
        $dialect = new stdClass();
        $dialect->delimiter = $parameter->getDelimiter();
        $dialect->quotechar = $parameter->getQuoteChar();
        $dialect->escapechar = $parameter->getEscapeChar();
        $dialect->lineterminator = $parameter->getLineTerminator();

        return $dialect;
    }

}
