<?php

require __DIR__ . '/vendor/autoload.php';


kirby()->hook('panel.page.update', function($page) {
  
  $settings = c::get('kirby-algolia');

  $index = new \KirbyAlgolia\Index($settings['algolia']);
  $parser = new \KirbyAlgolia\Parser($page, $settings['fields'], 'fragment');
  
  $records = $parser->parse();

  $index->save($records, $page, 'fragment');

});


?>