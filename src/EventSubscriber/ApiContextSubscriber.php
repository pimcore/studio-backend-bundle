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

namespace Pimcore\Bundle\StudioBackendBundle\EventSubscriber;

use Pimcore\Bundle\StaticResolverBundle\Lib\PimcoreResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StudioBackendPathTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
final readonly class ApiContextSubscriber implements EventSubscriberInterface
{
    use StudioBackendPathTrait;

    public function __construct(
        private string $urlPrefix,
        private DataObjectResolverInterface $dataObjectResolver,
        private DocumentResolverInterface $documentResolver,
        private LocalizedFieldResolverInterface $localizedFieldResolver,
        private PimcoreResolverInterface $pimcoreResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$this->isStudioBackendPath($request->getPathInfo(), $this->urlPrefix)) {
            return;
        }

        $this->pimcoreResolver->setAdminMode();
        $this->dataObjectResolver->setHideUnpublished(false);
        $this->documentResolver->setHideUnpublished(false);
        $this->localizedFieldResolver->setGetFallbackValues(false);
    }
}
