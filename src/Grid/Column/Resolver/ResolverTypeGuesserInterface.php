<?php

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver;

/**
 * @internal
 * */
interface ResolverTypeGuesserInterface
{
    public function guessType(string $key, string $classId): string;
}
