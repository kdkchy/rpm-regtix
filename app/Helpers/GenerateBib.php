<?php

namespace App\Helpers;

use App\Models\Registration;
use Illuminate\Support\Facades\DB;

class GenerateBib {

    public function generateRegId($count): string
    {
        $next = $count + 1;

        return str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}