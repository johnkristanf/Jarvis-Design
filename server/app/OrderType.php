<?php

namespace App;

enum OrderType: string
{
    case UPLOADED = 'uploaded';
    case PRE_MADE = 'pre-made';
}
