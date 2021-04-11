<?php
use PHPUnit\Framework\TestCase;
use KirbyAlgolia\Parser;

final class ParserTest extends TestCase
{
  private const PATH_GOLDEN_FILES = "tests/suites/golden";
  private $parser;

  protected function setUp(): void
  {
    $settings = option("mlbrgl.kirby-algolia");
    $this->parser = new Parser($settings);
  }

  private function writeGoldenFile($pageId)
  {
    $fragments = $this->parser->parse(page($pageId));
    file_put_contents(
      self::getPathGoldenFile($pageId),
      json_encode($fragments)
    );
  }

  private static function getPathGoldenFile($pageId)
  {
    return self::PATH_GOLDEN_FILES . "/" . $pageId . ".json";
  }

  private function parseAndAssert($pageId)
  {
    $fragments = $this->parser->parse(page($pageId));

    $this->assertEquals(
      json_decode(file_get_contents(self::getPathGoldenFile($pageId)), true),
      $fragments
    );
  }

  public function testParseArticle()
  {
    $pageId = "article";
    // $this->writeGoldenFile($pageId);
    $this->parseAndAssert($pageId);
  }

  public function testParseArticleWithHeadlessParagraph()
  {
    $pageId = "article-headless";
    // $this->writeGoldenFile($pageId);
    $this->parseAndAssert($pageId);
  }

  public function testParseArticleWithNoHeadings()
  {
    $pageId = "article-no-headings";
    // $this->writeGoldenFile($pageId);
    $this->parseAndAssert($pageId);
  }

  public function testParseArticleWithNoTeaser()
  {
    $pageId = "article-no-teaser";
    // $this->writeGoldenFile($pageId);
    $this->parseAndAssert($pageId);
  }
}
