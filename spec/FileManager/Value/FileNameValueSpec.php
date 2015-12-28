<?php

namespace spec\Spot\FileManager\Value;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\FileManager\Value\FileNameValue;

/** @mixin  FileNameValue */
class FileNameValueSpec extends ObjectBehavior
{
    private $value;

    public function let()
    {
        $this->value = 'file-name.txt';
        $this->beConstructedThrough('get', [$this->value]);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(FileNameValue::class);
    }

    public function it_canGetItsStringValue()
    {
        $this->toString()->shouldReturn($this->value);
    }

    public function it_willNotAcceptInvalidFileNames()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("some\0file");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("some\rfile");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("some\n");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("..file");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("/some/file/");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("some.file ");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet(" some.file");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("some<script>file");
    }
}
