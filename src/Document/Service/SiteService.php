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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Site\SiteResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\SiteEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Hydrator\SiteHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\ExcludeMainSiteParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\Repository\SiteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Site;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\UpdateSiteParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderIds;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderPaths;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class SiteService implements SiteServiceInterface
{
    use UserPermissionTrait;

    public function __construct(
        private DocumentServiceInterface $documentService,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
        private SiteHydratorInterface $siteHydrator,
        private SiteRepositoryInterface $siteRepository,
        private SiteResolverInterface $siteResolver,
        private ToolResolverInterface $toolResolver,
    ) {
    }

    /**
     * @return Site[]
     */
    public function getAvailableSites(ExcludeMainSiteParameter $parameter): array
    {
        $sites = [];
        if ($parameter->getExcludeMainSite() === false) {
            $sites[] = $this->getMainSite();
        }

        $siteList = $this->siteRepository->listSites();
        foreach ($siteList as $siteEntry) {
            $site = $this->siteHydrator->hydrate($siteEntry);

            $this->eventDispatcher->dispatch(new SiteEvent($site), SiteEvent::EVENT_NAME);
            $sites[] = $site;
        }

        return $sites;
    }

    /**
     * {@inheritdoc}
     */
    public function updateSite(int $documentId, UpdateSiteParameters $parameters): void
    {
        $this->documentService->getDocumentElement($this->securityService->getCurrentUser(), $documentId);

        $site = $this->siteResolver->getByRootId($documentId);
        try {
            if ($site === null) {
                $site = $this->siteResolver->create(['rootId' => $documentId]);
            }

            $site->setMainDomain($parameters->getMainDomain());
            $site->setDomains($parameters->getDomains());
            $site->setErrorDocument($parameters->getErrorDocument());
            $site->setLocalizedErrorDocuments($this->getLocalizedErrorDocuments($parameters->getLocalizedErrorDocuments()));
            $site->setRedirectToMainDomain($parameters->isRedirectToMainDomain());
            $site->save();
        } catch (Exception $exception) {
            throw new ElementSavingFailedException($documentId, $exception->getMessage(), $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteSite(int $documentId): void
    {
        $this->documentService->getDocumentElement($this->securityService->getCurrentUser(), $documentId);
        $site = $this->siteResolver->getByRootId($documentId);

        if ($site === null) {
            throw new NotFoundException(type: 'site', id: $documentId, parameter: 'document ID');
        }

        $site->delete();
    }

    private function getMainSite(): Site
    {
        return new Site(0, [], 'main_site', ElementFolderIds::ROOT->value, ElementFolderPaths::ROOT->value);
    }

    private function getLocalizedErrorDocuments(array $parameterDocuments): array
    {
        if (count($parameterDocuments) === 0) {
            return [];
        }

        $languages = $this->toolResolver->getValidLanguages();
        foreach ($parameterDocuments as $language => $path) {
            if (!in_array($language, $languages, true)) {
                unset($parameterDocuments[$language]);
            }
        }

        return $parameterDocuments;
    }
}
