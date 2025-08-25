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
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\AdminResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Model\UserInterface;
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
        private AdminResolverInterface $adminResolver,
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
        if ($domain === 'admin' && !$user->isAllowed('admin_translations')) {
            throw new ForbiddenException('User does not have permission: admin_translations');
        }

        $allowedLanguages = $this->getAllowedLanguages($user, $domain);
        $translations = $this->prepareTranslationData($domain, $parameter, $allowedLanguages);
        $columns = $this->prepareColumns($translations, $allowedLanguages);
        $csvContent = $this->buildCsvContent($translations, $columns);

        return $this->generateCsvResponse($csvContent, $domain);
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

    private function buildCsvContent(array $translations, array $columns): string
    {
        $csv = $this->buildHeaderRow($columns);
        $csv .= $this->buildDataRows($translations, $columns);

        return $csv;
    }

    private function buildHeaderRow(array $columns): string
    {
        $headerRow = [];
        foreach ($columns as $column) {
            $headerRow[] = '"' . $column . '"';
        }

        return implode(';', $headerRow) . "\r\n";
    }

    private function buildDataRows(array $translations, array $columns): string
    {
        $rows = '';
        foreach ($translations as $translation) {
            $tempRow = [];
            foreach ($columns as $column) {
                $value = $translation[$column] ?? null;
                $tempRow[$column] = $this->formatCellValue($value);
            }
            $rows .= implode(';', $tempRow) . "\r\n";
        }

        return $rows;
    }

    private function formatCellValue(mixed $value): string
    {
        if (!is_string($value)) {
            return (string) $value;
        }

        $value = $this->removeLineBreaks($value);
        $value = str_replace('"', '&quot;', $value);

        return '"' . $value . '"';
    }

    private function getAllowedLanguages(UserInterface $user, string $domain): array
    {
        $allowedLanguages = $user->getAllowedLanguagesForViewingWebsiteTranslations();
        if (in_array($domain, [TranslatorServiceInterface::DOMAIN, 'admin'], true)) {
            $allowedLanguages = $this->adminResolver->getLanguages();
        }

        return $allowedLanguages;
    }

    private function removeLineBreaks(string $text): string
    {
        $text = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $text);

        return preg_replace(pattern: '# +#', replacement: ' ', subject: $text);
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
}
