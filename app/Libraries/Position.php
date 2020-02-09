<?php

namespace App\Libraries;

class Position
{
    /** @var int */
    public $x;
    /** @var int */
    public $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
}
