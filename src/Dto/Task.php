<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Dto;

class Task
{
    public function __construct(private readonly \Pimcore\Model\Schedule\Task $task)
    {
    }

    public function getId(): ?int
    {
        return $this->task->getId();
    }

    public function getCid(): ?int
    {
        return $this->task->getCid();
    }

    public function getCtype(): ?string
    {
        return $this->task->getCtype();
    }

    public function getDate(): ?int
    {
        return $this->task->getDate();
    }

    public function getAction(): ?string
    {
        return $this->task->getAction();
    }

    public function getVersion(): ?int
    {
        return $this->task->getVersion();
    }
}