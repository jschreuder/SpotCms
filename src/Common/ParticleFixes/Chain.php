<?php

namespace Spot\Common\ParticleFixes;

class Chain extends \Particle\Validator\Chain
{

    /** {@inheritdoc} */
    public function uuid($version = Rule\Uuid::UUID_V4)
    {
        return $this->addRule(new Rule\Uuid($version));
    }
}
