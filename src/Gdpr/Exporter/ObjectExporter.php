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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Exporter;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal
 */
final readonly class ObjectExporter implements ObjectExporterInterface
{
    public function __construct(
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private DataServiceInterface $dataService,
        private NormalizerInterface $normalizer,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function doExportObject(Concrete $object, array &$result = []): void
    {
        $this->dataObjectServiceResolver->useInheritedValues(
            true,
            function () use ($object, &$result): void {
                foreach ($object->getClass()->getFieldDefinitions() as $fd) {
                    $getter = 'get' . ucfirst($fd->getName());
                    $value = $this->dataService->getNormalizedValue($object->$getter(), $fd);
                    $normalized = $this->normalizer->normalize($value);
                    $result[$fd->getName()] = $normalized; // @phpstan-ignore parameterByRef.type
                }
            }
        );
    }
}
