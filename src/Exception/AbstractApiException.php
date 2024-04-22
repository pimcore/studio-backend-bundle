<?php

namespace Pimcore\Bundle\StudioApiBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class AbstractApiException extends HttpException implements ApiExceptionInterface
{

}