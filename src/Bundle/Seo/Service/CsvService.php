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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service;

use Exception;
use Pimcore\Bundle\SeoBundle\Redirect\Csv;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Event\PreResponse\RedirectImportStatsEvent;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator\RedirectImportStatsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Repository\RedirectsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectImportStats;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @internal
 */
final readonly class CsvService implements CsvServiceInterface
{
    public function __construct(
        private Csv $csvService,
        private EventDispatcherInterface $eventDispatcher,
        private RedirectImportStatsHydratorInterface $hydrator,
        private RedirectsRepositoryInterface $repository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function exportRedirects(): Response
    {
        $listing = $this->repository->getListing();
        $listing->setOrderKey('id');
        $listing->setOrder('ASC');

        try {
            $writer = $this->csvService->createExportWriter($listing);
            $response = new Response();
            $response->headers->set(HttpResponseHeaders::HEADER_CONTENT_ENCODING->value, 'none');
            $response->headers->set(HttpResponseHeaders::HEADER_CONTENT_TYPE->value, 'text/csv; charset=UTF-8');
            $response->headers->set(
                HttpResponseHeaders::HEADER_CONTENT_DISPOSITION->value,
                $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'redirects.csv'
                )
            );

            $response->setContent($writer->toString());
        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage());
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function importRedirects(string $filePath): RedirectImportStats
    {
        try {
            $statistics = $this->csvService->import($filePath);
        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage());
        }

        $entry = $this->hydrator->hydrate($statistics);
        $this->eventDispatcher->dispatch(
            new RedirectImportStatsEvent($entry),
            RedirectImportStatsEvent::EVENT_NAME
        );

        return $entry;
    }
}
