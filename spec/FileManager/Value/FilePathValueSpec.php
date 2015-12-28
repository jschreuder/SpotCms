<?php

namespace spec\Spot\FileManager\Value;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spot\FileManager\Value\FilePathValue;

/** @mixin  FilePathValue */
class FilePathValueSpec extends ObjectBehavior
{
    private $value;

    public function let()
    {
        $this->value = '/file path/txt_/dìr/';
        $this->beConstructedThrough('get', [$this->value]);
    }

    public function it_isInitializable()
    {
        $this->shouldHaveType(FilePathValue::class);
    }

    public function it_canGetItsStringValue()
    {
        $this->toString()->shouldReturn($this->value);
    }

    public function it_willNotAcceptInvalidFileNames()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("/some\0file/");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("/some\rfile/");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("/some\n/");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("/../file/");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("/some.file /");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("/ some.file/");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("/some<script>file/");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet("/somefile/ ");
        $this->shouldThrow(\InvalidArgumentException::class)->duringGet(" /somefile/");
    }
}
