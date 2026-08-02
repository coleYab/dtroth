<?php

namespace App\Enums;

enum WebhookDeliveryStatus: string
{
    case Pending = 'pending';
    case Retrying = 'retrying';
    case Success = 'success';
    case Failed = 'failed';
}
