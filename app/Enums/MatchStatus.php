<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Matched = 'matched';
    case Unmatched = 'unmatched';
    case Uncertain = 'uncertain';
    case Skipped = 'skipped';
}
