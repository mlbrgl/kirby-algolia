<?php
use PHPUnit\Framework\TestCase;
use KirbyAlgolia\Index;

final class IndexTest extends TestCase
{
  private $settings;

  protected function setUp(): void
  {
    $this->settings = option("mlbrgl.kirby-algolia");
  }

  public function testIsPageIndexable()
  {
    $this->assertEquals(
      true,
      Index::is_page_indexable(page("article"), $this->settings)
    );
  }

  public function testIsUnlistedPageNotIndexable()
  {
    $this->assertEquals(
      false,
      Index::is_page_indexable(
        page("unlisted-article-not-indexable"),
        $this->settings
      )
    );
  }

  public function testIsMissingConfigurationBlueprintNotIndexable()
  {
    $this->assertEquals(
      false,
      Index::is_page_indexable(page("post-not-indexable"), $this->settings)
    );
  }
}
