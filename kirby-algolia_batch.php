<?php

require_once __DIR__ . "/../../../kirby/bootstrap.php";
use KirbyAlgolia\Index;
use KirbyAlgolia\Parser;

$debug = false;

$kirby = new Kirby([]);

define("BATCH_SIZE", 50);

$settings = option("mlbrgl.kirby-algolia");
$index = new Index($settings);
$parser = new Parser($settings);
$fragments = [];
$page_count = $fragment_count = 0;

$pages = site()->index();

$time_start = microtime(true);

foreach ($pages as $page) {
  if (!Index::is_page_indexable($page, $settings)) {
    continue;
  }
  $page_count++;

  $fragments = array_merge($fragments, $parser->parse($page));
  if ($debug) {
    print $page_count .
      " - " .
      $page->title() .
      " - " .
      get_formatted_memory_usage() .
      PHP_EOL;
  }

  if (!($page_count % BATCH_SIZE)) {
    $index->send_fragments_algolia($fragments);
    $fragment_count += count($fragments);
    $fragments = [];
  }
}

$time_end = microtime(true);

// Send last batch to Algolia;
if ($page_count % BATCH_SIZE) {
  $index->send_fragments_algolia($fragments);
  $fragment_count += count($fragments);
}

print "Parsed {$page_count} pages ({$fragment_count} fragments) in " .
  $time_end -
  $time_start .
  " s" .
  PHP_EOL;
print "Memory usage: " . get_formatted_memory_usage();

function get_formatted_memory_usage()
{
  return round(memory_get_usage() / 1048576, 2) . " MB";
}
