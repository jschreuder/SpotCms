<?php

namespace Spot\Common\ParticleFixes\Rule;

class Uuid extends \Particle\Validator\Rule\Uuid
{
    /**
     * An array of all validation regexes.
     *
     * @var array
     */
    protected $regexes = [
        4 => '~^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$~i',
    ];
}
