<?php

require __DIR__ . '/vendor/autoload.php';


// Bootstrapping Kirby (from index.php)
define('DS', DIRECTORY_SEPARATOR);

// load kirby
require('..' . DS . '..' . DS . '..' . DS . 'kirby' . DS . 'bootstrap.php');

// check for a custom site.php
if(file_exists('..' . DS . '..' . DS . '..' . DS . 'site.php')) {
  require('..' . DS . '..' . DS . '..' . DS . 'site.php');
} else {
  $kirby = kirby();
}

// Lighter version of $kirby->lauch() as we are only interested in
// bootstrapping kirby here, not rendering a page

// set the timezone for all date functions
date_default_timezone_set($kirby->options['timezone']);
// this will trigger the configuration
$site = $kirby->site();
// load all extensions
$kirby->extensions();
// load all plugins
$kirby->plugins();
// load all models
$kirby->models();


// Getting Algolia configuration
$settings = c::get('kirby-algolia');

// Initializing Index and Parser
$index = new \KirbyAlgolia\Index($settings);
$parser = new \KirbyAlgolia\Parser($index, 'fragments');
$count = 0;

// Getting a collection of all pages in the site and processing
// only those which content type we are interested in
$pages = $site->index()->visible();

foreach ($pages as $page) {

  if(array_key_exists($page->intendedTemplate(), $settings['blueprints'])) {
    $count ++;
    $parser->parse($page, $settings['blueprints'][$page->intendedTemplate()]['fields']);

    print $count . ' - ' . round(memory_get_usage()/1048576,2).' MB' . PHP_EOL;
    
    if(!($count % 50)){
      print '## INDEXING ##' . PHP_EOL;
      // Save current batch
      $index->update('batch');
    }
  }
}

// Indexing the last batch
if(($count % 50)){
  print '## INDEXING ##' . PHP_EOL;
  // Save current batch
  $index->update('batch');
}




