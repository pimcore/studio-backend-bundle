<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Dto;

class Dependency
{
    public function __construct(private readonly \Pimcore\Model\Dependency $dependency)
    {
    }

    public function getSourceId(): int
    {
        return $this->dependency->getSourceId();
    }

    public function getRequires(int $offset = null, int $limit = null): array
    {
        return $this->dependency->getRequires($offset, $limit);
    }

    public function getFilterRequiresByPath(int $offset = null, int $limit = null, string $value = null): array
    {
        return $this->dependency->getFilterRequiresByPath($offset, $limit, $value);
    }

    public function getFilterRequiredByPath(int $offset = null, int $limit = null, string $value = null): array
    {
        return $this->dependency->getFilterRequiredByPath($offset, $limit, $value);
    }

    public function getRequiredBy(int $offset = null, int $limit = null): array
    {
        return $this->dependency->getRequiredBy($offset, $limit);
    }

    public function getSourceType(): string
    {
        return $this->dependency->getSourceType();
    }

    public function getRequiresTotalCount(): int
    {
        return $this->dependency->getRequiresTotalCount();
    }

    public function getRequiredByTotalCount(): int
    {
        return $this->dependency->getRequiredByTotalCount();
    }

    public function isRequired(): bool
    {
        return $this->dependency->isRequired();
    }
}