<?php

namespace App\Enums;

enum EnvironmentName: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';
}
