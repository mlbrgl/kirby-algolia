<?php

namespace KirbyAlgolia;

class Index {

  private $application_id;
  private $index;
  private $api_key;

  //DEBUG
  //TODO option
  const DEBUG = 0;
  const ENABLE_INDEXING = 1;
  private $debug_path;

  public function __construct($algolia_settings) {
    //TODO exception
    if(  !empty($algolia_settings['application_id'])
      && !empty($algolia_settings['index'])
      && !empty($algolia_settings['api_key'])) {
    
      $this->application_id = $algolia_settings['application_id'];
      $this->index = $algolia_settings['index'];
      $this->api_key = $algolia_settings['api_key'];
    }

    if(self::DEBUG) {
      $this->debug_path = __DIR__ . '/indexed.txt';
      if(\f::exists($this->debug_path)){
        \f::remove($this->debug_path);
      }
    }
  }


  public function save($records, $page, $indexing_type) {
    if(!empty($records)){

      // Init Algolia's index
      $client = new \AlgoliaSearch\Client($this->application_id, $this->api_key);
      $index = $client->initIndex($this->index);
      
      if ($indexing_type == 'fragment') {
        // Before indexing new fragments, we need to remove all fragments of the same previously indexed content,
        // to prevent leaving ghost fragments in case a heading has been renamed. These fragments all share the 
        // same root.
        $index->deleteByQuery($page->id(), array('restrictSearchableAttributes' => '_fragment_id'));
      }

      if(self::ENABLE_INDEXING){        
        $res = $index->addObjects($records); 
        
        if(self::DEBUG) {
          \f::write($this->debug_path, '## Returned by addObjects' . PHP_EOL . print_r($res, true), true);
        } 
      }
      
    }
    
  }

}

?>