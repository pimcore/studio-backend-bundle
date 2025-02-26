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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Legacy;

use Exception;
use Locale;
use LogicException;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Site\SiteResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\ClassDefinition\PreviewGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Site\Listing;
use Pimcore\Model\Translation;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_key_exists;
use function in_array;
use function is_null;

/**
 * @internal - This class was mostly copied from AdminClassicBundle
 */
final readonly class PreviewGenerator implements PreviewGeneratorInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private SiteResolverInterface $siteResolver,
        private ToolResolverInterface $toolResolver,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @throws Exception
     */
    public function generatePreviewUrl(Concrete $object, array $params): string
    {
        $linkGenerator = $object->getClass()->getLinkGenerator();

        if ($linkGenerator instanceof LinkGeneratorInterface) {
            $filteredParameters = $this->filterParameters($object, $params);

            $locale = $this->toolResolver->getDefaultLanguage();

            if ($filteredParameters[PreviewGeneratorInterface::PARAMETER_LOCALE]) {
                $locale = $filteredParameters[PreviewGeneratorInterface::PARAMETER_LOCALE];
            }

            if (array_key_exists(PreviewGeneratorInterface::PARAMETER_SITE, $filteredParameters)) {
                $site = $this->siteResolver->getById($filteredParameters[PreviewGeneratorInterface::PARAMETER_SITE]);
            } else {
                $site = (new Listing())->current();
            }

            return $linkGenerator->generate($object, [
                PreviewGeneratorInterface::PARAMETER_LOCALE => $locale,
                PreviewGeneratorInterface::PARAMETER_SITE => $site,
            ]);
        }

        throw new LogicException("No link generator given for element of type {$object->getClassName()}");
    }

    public function getPreviewConfig(Concrete $object): array
    {
        return array_filter([
            $this->getLocalePreviewConfig(),
            $this->getSitePreviewConfig(),
        ]);
    }

    private function filterParameters(Concrete $object, array $parameters): array
    {
        $previewConfig = $this->getPreviewConfig($object);

        $filteredParameters = [];
        foreach ($previewConfig as $config) {
            $name = $config['name'];
            $selectedValue = $parameters[$name] ?? $config['defaultValue'];

            if (!empty($selectedValue)) {
                $filteredParameters[$name] = $selectedValue;
            }
        }

        return $filteredParameters;
    }

    private function getLocalePreviewConfig(): array
    {
        $user = $this->securityService->getCurrentUser();
        $userLocale = $user->getLanguage();

        $locales = [];
        $validLanguages = $this->toolResolver->getValidLanguages();

        foreach ($validLanguages as $locale) {
            $label = Locale::getDisplayLanguage($locale, $userLocale);
            $locales[$label] = $locale;
        }

        $defaultValue = $this->toolResolver->getDefaultLanguage();

        if (in_array($userLocale, $validLanguages, true)) {
            $defaultValue = $userLocale;
        }

        return [
            'name' => PreviewGeneratorInterface::PARAMETER_LOCALE,
            'label' => $this->translator->trans('preview_generator_locale', [], Translation::DOMAIN_ADMIN),
            'values' => $locales,
            'defaultValue' => $defaultValue,
        ];
    }

    protected function getSitePreviewConfig(): array
    {
        $sites = new Listing();
        $sites->setOrderKey('mainDomain')->setOrder('ASC');

        if ($sites->count() === 0) {
            return [];
        }

        $sitesOptions = [
            $this->translator->trans('main_site', [], Translation::DOMAIN_ADMIN) => '0',
        ];

        $preSelectedSite = null;
        foreach ($sites as $site) {
            if ($site->getId() === null) {
                continue;
            }

            $label = $site->getRootDocument()?->getKey();
            $sitesOptions[$label] = $site->getId();

            $domains = $site->getDomains();
            array_unshift($domains, $site->getMainDomain());

            if (
                is_null($preSelectedSite) &&
                in_array($this->toolResolver->getHostname(), $domains, true)
            ) {
                $preSelectedSite = $sitesOptions[$label];
            }
        }

        return [
            'name' => PreviewGeneratorInterface::PARAMETER_SITE,
            'label' => $this->translator->trans('preview_generator_site', [], Translation::DOMAIN_ADMIN),
            'values' => $sitesOptions,
            'defaultValue' => $preSelectedSite ?: reset($sitesOptions),
        ];
    }
}
