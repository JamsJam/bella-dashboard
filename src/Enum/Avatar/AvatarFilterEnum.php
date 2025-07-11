<?php

namespace App\Enum\Avatar;

enum AvatarFilterEnum: string
{
    case COLOR_AND_SHAPE_FILTER = '1';
    case SKIN_AND_SHAPE_FILTER = '2';
    case BODY_FILTER = '3';
}
