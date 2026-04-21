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

use Pimcore\Localization\LocaleServiceInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use function count;

/**
 * @internal
 */
final readonly class AdminLanguageService implements AdminLanguageServiceInterface
{
    public function __construct(
        private string $translationsPath,
        private string $defaultTranslationsPath,
        private KernelInterface $kernel,
        private LocaleServiceInterface $localeService,
    ) {
    }

    public function getAvailableAdminLanguages(): array
    {
        $translatedLanguages = [];

        foreach ($this->getLanguageDirectories() as $directory) {
            $translatedLanguages = array_merge($translatedLanguages, $this->scanDirectoryForLanguages($directory));
        }

        return array_unique($translatedLanguages);
    }

    private function getLanguageDirectories(): array
    {
        $directories = [];

        $languageDir = $this->kernel->locateResource($this->translationsPath);
        if (is_dir($languageDir)) {
            $directories[] = $languageDir;
        }

        if (is_dir($this->defaultTranslationsPath)) {
            $directories[] = $this->defaultTranslationsPath;
        }

        return $directories;
    }

    private function scanDirectoryForLanguages(string $directory): array
    {
        $languages = [];
        $files = scandir($directory);

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $languageCode = $this->extractLanguageCode($directory, $file);
            if ($languageCode !== null) {
                $languages[] = $languageCode;
            }
        }

        return $languages;
    }

    private function extractLanguageCode(string $directory, string $file): ?string
    {
        if (!is_file($directory . '/' . $file)) {
            return null;
        }

        $parts = explode('.', $file);
        if (count($parts) < 2) {
            return null;
        }

        $languageCode = $parts[0];
        if ($parts[0] === 'studio' && isset($parts[1])) {
            $languageCode = $parts[1];
        }

        $extension = end($parts);
        if (($extension === 'json' || $parts[0] === 'studio') && $this->localeService->isLocale($languageCode)) {
            return $languageCode;
        }

        return null;
    }
}
