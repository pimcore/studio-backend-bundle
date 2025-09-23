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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Site;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\SiteDetail;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Site as SiteModel;

/**
 * @internal
 */
final class SiteHydrator implements SiteHydratorInterface
{
    public function __construct(
        private ElementDataServiceInterface $elementDataService,
        private ElementServiceInterface $elementService,
        private SecurityServiceInterface $securityService,
    ) {
    }

    public function hydrate(SiteModel $siteModel): Site
    {
        return new Site(
            $siteModel->getId(),
            $siteModel->getDomains(),
            $siteModel->getMainDomain(),
            $siteModel->getRootId(),
            $siteModel->getRootPath(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateDetail(SiteModel $siteModel): SiteDetail
    {
        return new SiteDetail(
            $siteModel->getId(),
            $siteModel->getCreationDate(),
            $siteModel->getModificationDate(),
            $siteModel->getMainDomain(),
            $siteModel->getDomains(),
            $this->getElementData($siteModel->getErrorDocument()),
            $this->getLocalizedErrorDocuments($siteModel->getLocalizedErrorDocuments()),
            $siteModel->getRedirectToMainDomain()
        );
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    private function getLocalizedErrorDocuments(array $localizedDocuments): array
    {
        if (empty($localizedDocuments)) {
            return [];
        }

        return array_map(function ($localizedDocument) {
            return $this->getElementData($localizedDocument);
        }, $localizedDocuments);
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    private function getElementData(string $elementPath): ?RelatedElementData
    {
        if ($elementPath === '') {
            return null;
        }

        $element = $this->elementService->getAllowedElementByPath(
            ElementTypes::TYPE_DOCUMENT,
            $elementPath,
            $this->securityService->getCurrentUser()
        );

        return $this->elementDataService->getRelatedElementData($element);
    }
}
