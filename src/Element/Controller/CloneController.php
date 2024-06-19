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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter\ElementCloneParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Request\SingleParameterRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CloneController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly CloneServiceInterface $cloneService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException|NotFoundException
     */
    #[Route('/elements/{elementType}/clone/{id}', name: 'pimcore_studio_api_user_clone', methods: ['POST'])]
    #[IsGranted(UserPermissions::USER_MANAGEMENT->value)]
    #[Post(
        path: self::API_PATH . '/elements/{elementType}/clone/{id}',
        operationId: 'cloneElement',
        summary: 'Clone a specific element.',
        tags: [Tags::Elements->value]
    )]
    #[SuccessResponse(
        description: 'Successfully copied element',
    )]
    #[IdParameter(type: 'element')]
    #[ElementTypeParameter]
    #[SingleParameterRequestBody(
        parameterName: 'name',
        example: 'Cloned User'
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function cloneElement(
        int $id,
        string $elementType,
        #[MapRequestPayload] ElementCloneParameter $cloneParameter): Response
    {
        $this->cloneService->cloneElement($id, $cloneParameter, $elementType);

        return new Response();
    }
}
