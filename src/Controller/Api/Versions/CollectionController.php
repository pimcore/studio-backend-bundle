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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Controller\Api\Versions;

use Exception;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\ElementTypeParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\IdParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\UnauthorizedResponse;
use Pimcore\Bundle\StudioApiBundle\Config\Tags;
use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Controller\Trait\PaginatedResponseTrait;
use Pimcore\Bundle\StudioApiBundle\Request\Query\Version as VersionQuery;
use Pimcore\Bundle\StudioApiBundle\Service\Filter\FilterServiceInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Exception\NotFoundException;
use Pimcore\Model\Version\Listing;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly FilterServiceInterface $filterService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws Exception
     */
    #[Route('/versions', name: 'pimcore_studio_api_element_versions', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[GET(path: self::API_PATH
        .'/versions', description: 'Get versions of an element', summary: 'Get all versions of an specific element (Asset, DataObject, Document) by its ID.', security: self::SECURITY_SCHEME, tags: [Tags::Versions->name],)]
    #[IdParameter]
    #[ElementTypeParameter]
    #[SuccessResponse(description: 'Paginated versions with total count as header param', content: new JsonContent(
        ref: Version::class
    ))]
    #[UnauthorizedResponse]
    public function getVersions(
        #[MapQueryString] VersionQuery $versionQuery
    ): JsonResponse {
        $type = $versionQuery->getType();
        $id = $versionQuery->getId();
        $element = Service::getElementById($type, $id);

        if (!$element) {
            throw new NotFoundException('Element not found');
        }

        if (!$element->isAllowed('versions')) {
            throw $this->createAccessDeniedException('You are not allowed to view versions of this element');
        }

        $schedule = $element->getScheduledTasks();
        $schedules = [];
        foreach ($schedule as $task) {
            if ($task->getActive()) {
                $schedules[$task->getVersion()] = $task->getDate();
            }
        }

        //only load auto-save versions from current user
        $list = new Listing();
        $list->setLoadAutoSave(true);

        // TODO: Implement the filter of the user as it was on admin-ui-classic-bundle
        $list->setCondition('cid = ? AND ctype = ? AND (autoSave=0 OR (autoSave=1)) ', [
            $element->getId(),
            Service::getElementType($element),
        ])->setOrderKey('date')->setOrder('ASC');

        $versions = $list->load();

        $versions = Service::getSafeVersionInfo($versions);
        $versions = array_reverse($versions); //reverse array to sort by ID DESC
        foreach ($versions as &$version) {
            $version['scheduled'] = null;
            if (array_key_exists($version['id'], $schedules)) {
                $version['scheduled'] = $schedules[$version['id']];
            }
        }

        // TODO: This was just copied from other endpoint without customizing it for this endpoint.
        return $this->getPaginatedCollection($this->serializer, ['versions' => $versions]);
    }
}
