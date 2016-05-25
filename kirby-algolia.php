<?php

require __DIR__ . '/vendor/autoload.php';


kirby()->hook('panel.page.update', function($page) {

  // Getting Algolia configuration
  $settings = c::get('kirby-algolia');

  // Only interested in indexing visible pages, which are set in the config
  if($page->isVisible() && in_array($page->template(), $settings['content']['types'])) {
    $settings = c::get('kirby-algolia');

    $index = new \KirbyAlgolia\Index($settings['algolia']);
    $parser = new \KirbyAlgolia\Parser($index, $settings['fields'], 'fragment');
    
    $parser->parse($page);
    
    $fragments_shared_root = $page->id();
    $index->save('fragment', $fragments_shared_root);
  }
});


?>