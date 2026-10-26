<?php

declare(strict_types=1);

namespace Core\Session;

enum FlashType: string
{
    case FLASH_ERROR = 'error';
    case FLASH_SUCCESS = 'success';
    case FLASH_WARNING = 'warning';
    case FLASH_INFO = 'info';
}
