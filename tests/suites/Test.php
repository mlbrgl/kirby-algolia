<?php
use PHPUnit\Framework\TestCase;

final class Test extends TestCase {
  public function testTrue () {
    $this->assertEquals(true, true);
  }
}