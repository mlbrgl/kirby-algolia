<?php
use PHPUnit\Framework\TestCase;
use KirbyAlgolia\Fragment;

final class FragmentTest extends TestCase
{
  public function testGetRawTextFromKirbyText()
  {
    $kirbytext =
      "(image: myawesomepicture.jpg)\n\n#This\n**is** _the_ raw (link: https://devsante.org text:text)";

    $this->assertEquals(
      "This\nis the raw text",
      Fragment::kirby_to_raw_text($kirbytext)
    );
  }

  public function testGetRawTextFromNullKirbyText()
  {
    $kirbytext = null;

    $this->assertEquals("", Fragment::kirby_to_raw_text($kirbytext));
  }

  public function testGetRawTextFromEmptyKirbyText()
  {
    $kirbytext = "";

    $this->assertEquals("", Fragment::kirby_to_raw_text($kirbytext));
  }
}
