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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\PreviewParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\PreviewGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final readonly class PreviewUrlService implements PreviewUrlServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private PreviewGeneratorInterface $defaultPreviewGenerator,
        private ServiceResolverInterface $serviceResolver
    ) {
    }

    /**
     * @throws Exception|NotFoundException
     */
    public function getPreviewUrl(PreviewParameter $previewParameter): string
    {
        $dataObject = $this->getElement(
            $this->serviceResolver,
            ElementTypes::TYPE_OBJECT,
            $previewParameter->getId()
        );

        if (!$dataObject instanceof Concrete) {
            throw new NotFoundException('Data Object', $previewParameter->getId());
        }

        $url = $this->getPreviewGenerator($dataObject)?->generatePreviewUrl(
            $dataObject,
            ['preview' => true, 'context' => $this]
        );

        if (!$url) {
            throw new InvalidArgumentException('Could not generate preview url');
        }

        return $this->buildRedirectUrl($url, $previewParameter);
    }

    /**
     * @throws Exception
     */
    private function getPreviewGenerator(Concrete $dataObject): ?PreviewGeneratorInterface
    {
        $previewService = $dataObject->getClass()->getPreviewGenerator();

        if (!$previewService && $dataObject->getClass()->getLinkGenerator()) {
            $previewService = $this->defaultPreviewGenerator;
        }

        return $previewService;
    }

    private function buildRedirectUrl(string $url, PreviewParameter $previewParameter): string
    {
        // replace all remaining % signs
        $url = str_replace('%', '%25', $url);
        $urlParts = parse_url($url);

        $redirectParameters = array_filter([
            'pimcore_studio_preview' => true,
            'pimcore_object_preview' => $previewParameter->getId(),
            'site' => $previewParameter->getSite(),
            'dc' => time(),
        ]);

        $previewUrl = $urlParts['path'] . '?' . http_build_query($redirectParameters);

        if (isset($urlParts['query'])) {
            $previewUrl .= '&' . $urlParts['query'];
        }

        return $previewUrl;
    }
}
