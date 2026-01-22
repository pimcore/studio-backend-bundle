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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ThumbnailController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws RateLimitException|DomainConfigurationException|SendMailException
     */
    #[Route('/setting/admin/thumbnail', name: 'pimcore_studio_api_admin_settings_thumbnail', methods: ['POST'])]
    #[IsGranted(self::VOTER_PUBLIC_STUDIO_API, 'settingsAdminThumbnail')]
    #[Post(
        path: self::PREFIX . '/setting/admin/thumbnail',
        operationId: 'setting_admin_thumbnail',
        summary: 'setting_admin_thumbnail_summary',
        tags: [Tags::SettingsAdmin->value]
    )]
    #[SuccessResponse]
    #[DefaultResponses([
        HttpResponseCodes::TOO_MANY_REQUESTS,
    ])]
    public function settingsAdminThumbnail(?string $settingsAdminThumbnail = 'settingsAdminThumbnail'): Response
    {

        return new Response("test");
    }
}
