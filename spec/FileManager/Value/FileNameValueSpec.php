<?php

namespace spec\Spot\FileManager\Value;

use PhpSpec\ObjectBehavior;
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

    public function it_is_initializable()
    {
        $this->shouldHaveType(FileNameValue::class);
    }

    public function it_can_get_its_string_value()
    {
        $this->toString()->shouldReturn($this->value);
    }

    public function it_will_not_accept_invalid_file_names()
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
