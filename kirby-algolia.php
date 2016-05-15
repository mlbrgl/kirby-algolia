<?php

require __DIR__ . '/vendor/autoload.php';


kirby()->hook('panel.page.update', function($page) {
  
  // fields to index
  // TODO load from config
 
  // For fragment indexing :
  // - main fields content gets divided into fragments according to headings
  //   hn -> importance : n;
  // - boost fields content is treated atomically (not divided into fragments) and indexed separately ;
  //   their content gets boosted with an importance of 0 (highest);
  // - meta fields content is treated atomically and attached to each fragment

  $fields['meta'] = array('title', 'datetime'); // rendered as text
  $fields['boost'] = array('teaser'); // markdown expected, rendered through kirbytext()
  $fields['main'] = array('text'); // markdown expected, rendered through kirbytext()

  $algolia_settings['application_id'] = 'xxx';
  $algolia_settings['index'] = 'xxx';
  $algolia_settings['api_key'] = 'xxx';

  $index = new \KirbyAlgolia\Index($algolia_settings);
  $parser = new \KirbyAlgolia\Parser($page, $fields, 'fragment');
  
  $records = $parser->parse();
  $index->save($records);

});


?>