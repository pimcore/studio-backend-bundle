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
use League\Csv\CannotInsertRecord;
use League\Csv\Exception as CsvException;
use League\Csv\Writer;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Event\CsvSettingsEvent;
use Pimcore\Bundle\StudioBackendBundle\Translation\Hydrator\CsvSettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Schema\CsvSettings;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Tool\Text\Csv;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final readonly class CsvService implements CsvServiceInterface
{
    public function __construct(
        private CsvSettingsHydratorInterface $csvSettingsHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private LanguageServiceInterface $languageService,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $elementServiceResolver,
        private TranslationRepositoryInterface $translationRepository,
        private TranslatorServiceInterface $translatorService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function export(string $domain, CollectionFilterParameter $parameter): Response
    {
        $user = $this->securityService->getCurrentUser();
        $this->languageService->validateAdminPermission($user, $domain);

        $allowedLanguages = $this->languageService->getTranslationAllowedLanguages($user, $domain);
        $translations = $this->prepareTranslationData($domain, $parameter, $allowedLanguages);
        $columns = $this->prepareColumns($translations, $allowedLanguages);
        $csvContent = $this->buildCsvContent($translations, $columns);

        return $this->generateCsvResponse($csvContent, $domain);
    }

    public function determineCsvDialect(string $sample): CsvSettings
    {
        return $this->hydrateCsvSettings($this->getDialectClass($sample));
    }

    private function prepareTranslationData(
        string $domain,
        CollectionFilterParameter $parameter,
        array $allowedLanguages
    ): array {
        $translations = [];
        $list = $this->translatorService->getTranslationList($domain, $parameter);
        $list->setOffset(0);
        $list->setLimit(null);
        $translationObjects = $list->getTranslations();

        if (empty($translationObjects)) {
            $translationObjects[] = $this->translationRepository->createDummyTranslation($domain, $allowedLanguages);
        }

        foreach ($translationObjects as $translationObject) {
            $row = $translationObject->getTranslations();
            $row = $this->elementServiceResolver->escapeCsvRecord($row);
            $translations[] = array_merge(
                [
                    'key' => $translationObject->getKey(),
                    'creationDate' => $translationObject->getCreationDate(),
                    'modificationDate' => $translationObject->getModificationDate(),
                ],
                $row
            );
        }

        return $translations;
    }

    private function prepareColumns(array $translations, array $allowedLanguages): array
    {
        $columns = array_keys($translations[0]);

        foreach ($allowedLanguages as $language) {
            if (!in_array($language, $columns, true)) {
                $columns[] = $language;
            }
        }

        foreach ($columns as $key => $column) {
            if (!in_array($column, $allowedLanguages, true) &&
                strtolower(trim($column)) !== 'key') {
                unset($columns[$key]);
            }
        }

        return array_values($columns);
    }

    /**
     * @throws EnvironmentException
     */
    private function buildCsvContent(array $translations, array $columns): string
    {
        try {
            $csv = Writer::fromString();
            $csv->setDelimiter(';');
            $csv->setEndOfLine("\r\n");

            $csv->insertOne($columns);
            foreach ($translations as $translation) {
                $csv->insertOne($this->buildRecord($translation, $columns));
            }

            return $csv->toString();
        } catch (CannotInsertRecord | CsvException $e) {
            throw new EnvironmentException($e->getMessage());
        }
    }

    /**
     * Maps a translation to a record aligned with $columns. Embedded line breaks and quotes are
     * preserved: League\Csv\Writer applies RFC 4180 quoting/escaping instead of stripping them.
     *
     * @return list<string>
     */
    private function buildRecord(array $translation, array $columns): array
    {
        $record = [];
        foreach ($columns as $column) {
            $value = $translation[$column] ?? '';
            $record[] = is_string($value) ? $value : (string) $value;
        }

        return $record;
    }

    /**
     * @throws EnvironmentException
     */
    private function generateCsvResponse(string $csvContent, string $domain): Response
    {
        try {
            $response = new Response("\xEF\xBB\xBF" . $csvContent);
            $response->headers->set(HttpResponseHeaders::HEADER_CONTENT_ENCODING->value, 'UTF-8');
            $response->headers->set(HttpResponseHeaders::HEADER_CONTENT_TYPE->value, 'text/csv; charset=UTF-8');
            $response->headers->set(
                HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value,
                $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    sprintf('export_%s_translations.csv', $domain)
                )
            );

            return $response;
        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage());
        }
    }

    private function hydrateCsvSettings(stdClass $dialect): CsvSettings
    {
        $hydrated = $this->csvSettingsHydrator->hydrate($dialect);
        $this->eventDispatcher->dispatch(new CsvSettingsEvent($hydrated), CsvSettingsEvent::EVENT_NAME);

        return $hydrated;
    }

    private function getDialectClass(string $sample): stdClass
    {
        try {
            $sniffer = new Csv();
            $dialect = $sniffer->detect($sample);
        } catch (Exception) {
            $dialect = new stdClass();
            $dialect->delimiter = ';';
            $dialect->quotechar = '"';
            $dialect->escapechar = '\\';
            $dialect->lineterminator = "\r\n";
        }

        // ensure we have a valid delimiter
        if (!in_array($dialect->delimiter, [';', ',', "\t", '|', ':'])) {
            $dialect->delimiter = ';';
        }

        // Default to \r\n if line terminator is empty
        if (empty($dialect->lineterminator)) {
            $dialect->lineterminator = "\r\n";
        }

        // Hex-encode control characters for safe JSON transport
        if (!empty(preg_match('/[\x00-\x1f]/', $dialect->lineterminator))) {
            $dialect->lineterminator = bin2hex($dialect->lineterminator);
        }

        return $dialect;
    }
}
