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
$parser = new \KirbyAlgolia\Parser($index, $settings['fields'], 'fragments');

// Getting a collection of all pages in the site and processing
// only those which content type we are interested in
$pages = $site->index()->visible();
foreach ($pages as $page) {
  if(in_array($page->template(), $settings['content']['types'])) {
    $parser->parse($page);
  }
}

// Save all records in one go
$index->update('batch');
