<?php

namespace spec\Spot\DataModel\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\DataModel\Repository\NoUniqueResultException;

class NoUniqueResultExceptionSpec extends ObjectBehavior
{
    public function it_isInitializable()
    {
        $this->shouldHaveType(NoUniqueResultException::class);
    }
}
