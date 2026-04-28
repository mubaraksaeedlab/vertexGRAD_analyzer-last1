<?php

namespace App\Modules\Understanding\Enums;

enum EntityType: string
{
    case CLASS_TYPE = "class";
    case METHOD = "method";
    case FUNCTION = "function";
    case CONTROLLER = "controller";
    case MODEL = "model";
    case SERVICE = "service";
    case ROUTE = "route";
}