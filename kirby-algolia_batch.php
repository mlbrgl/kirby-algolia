<?php

require_once __DIR__ . "/../../../kirby/bootstrap.php";
use KirbyAlgolia\Index;
use KirbyAlgolia\Parser;

$debug = true;

$kirby = new Kirby([]);

define("BATCH_SIZE", 50);

$settings = option("mlbrgl.kirby-algolia");
$index = new Index($settings);
$parser = new Parser($settings);
$fragments = [];
$count = 0;

$pages = site()->index();

$time_start = microtime(true);

foreach ($pages as $page) {
  if (!Index::is_page_indexable($page, $settings)) {
    continue;
  }
  $count++;

  $fragments = array_merge($fragments, $parser->parse($page));
  if ($debug) {
    print $count .
      " - " .
      $page->title() .
      " - " .
      get_formatted_memory_usage() .
      PHP_EOL;
  }

  if (!($count % BATCH_SIZE)) {
    $index->send_fragments_algolia($fragments);
    $fragments = [];
  }
}

$time_end = microtime(true);

// Send last batch to Algolia;
if ($count % BATCH_SIZE) {
  $index->send_fragments_algolia($fragments);
}

print "Parsed {$count} pages in " . $time_end - $time_start . " s" . PHP_EOL;
print "Memory usage: " . get_formatted_memory_usage();

function get_formatted_memory_usage()
{
  return round(memory_get_usage() / 1048576, 2) . " MB";
}
