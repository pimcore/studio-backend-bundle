<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordRequest
{
    #[Assert\NotBlank]
    public string $username;

    public function getUsername(): string
    {
        return $this->username;
    }
}
