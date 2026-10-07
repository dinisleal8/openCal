<?php

namespace App\Enums;

enum FoodSource: string
{
    case Manual = 'manual';
    case AiPhoto = 'ai_photo';
    case AiText = 'ai_text';
    case Barcode = 'barcode';
}
