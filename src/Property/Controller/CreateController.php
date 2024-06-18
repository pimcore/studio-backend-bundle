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
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Property\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CreateController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly PropertyServiceInterface $propertyService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotWriteableException
     */
    #[Route('/property', name: 'pimcore_studio_api_create_property', methods: ['POST'])]
    #[IsGranted(UserPermissions::PREDEFINED_PROPERTIES->value)]
    #[POST(
        path: self::API_PATH . '/property',
        operationId: 'createProperty',
        summary: 'Creating new property with default values',
        tags: [Tags::Properties->name]
    )]
    #[SuccessResponse(
        description: 'Created predefined property',
        content: new JsonContent(ref: PredefinedProperty::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function createProperty(): JsonResponse
    {
        return $this->jsonResponse($this->propertyService->createPredefinedProperty());
    }
}
