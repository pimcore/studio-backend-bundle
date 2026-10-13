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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * A single `pimcore.documents.default_controller` serves every document type, so a project that
 * renders emails with a different controller than pages had to switch the controller by hand on
 * every new email document. The `documents.default_controllers` node lets a project set one per
 * page snippet type; everything not listed keeps the core default.
 *
 * @internal
 */
final class DocumentsConfigurationTest extends Unit
{
    private const string EMAIL_CONTROLLER = 'App\\Controller\\MailController::defaultAction';

    public function testAbsentDocumentsSectionYieldsNoPerTypeControllers(): void
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [[]]);

        $this->assertSame([], $processed['documents']['default_controllers']);
    }

    public function testAcceptsOneControllerPerPageSnippetType(): void
    {
        $controllers = [
            'page' => 'App\\Controller\\PageController::defaultAction',
            'snippet' => 'App\\Controller\\SnippetController::defaultAction',
            'email' => self::EMAIL_CONTROLLER,
        ];

        $config = $this->process(['default_controllers' => $controllers]);

        $this->assertSame($controllers, $config['default_controllers']);
    }

    public function testTypesWithoutEntryStayUnset(): void
    {
        $config = $this->process(['default_controllers' => ['email' => self::EMAIL_CONTROLLER]]);

        $this->assertSame(['email' => self::EMAIL_CONTROLLER], $config['default_controllers']);
    }

    public function testRejectsDocumentTypeWithoutController(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/link/');

        $this->process(['default_controllers' => ['link' => self::EMAIL_CONTROLLER]]);
    }

    public function testRejectsEmptyController(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['default_controllers' => ['email' => '']]);
    }

    private function process(array $documents): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [['documents' => $documents]])['documents'];
    }
}
