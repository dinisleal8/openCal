<?php

namespace App\Enums;

enum FoodSource: string
{
    case Manual = 'manual';
    case AiPhoto = 'ai_photo';
    case Barcode = 'barcode';
}
