<?php

namespace App\Enums;

enum GoalType: string
{
    case Lose = 'lose';
    case Maintain = 'maintain';
    case Gain = 'gain';
}
