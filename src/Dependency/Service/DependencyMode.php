<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Service;

/**
 * @internal
 */
enum DependencyMode: string
{
    case REQUIRED_BY = 'required_by';
    case REQUIRES = 'requires';
}