<?php

namespace KirbyAlgolia;

class Index {

  private $application_id;
  private $index;
  private $api_key;

  //DEBUG
  const DEBUG = 1;
  private $debug_path;

  public function __construct($algolia_settings) {
    $this->application_id = $algolia_settings['application_id'];
    $this->index = $algolia_settings['index'];
    $this->api_key = $algolia_settings['api_key'];

    if(DEBUG) {
      $this->debug_path = __DIR__ . '/indexed.txt';
      if(\f::exists($this->debug_path)){
        \f::remove($this->debug_path);
      }
    }
  }


  public function save($records) {
    if(DEBUG) {
      \f::write($this->debug_path, print_r($records, true), true);
    }
    if(!empty($records)){
      // TODO
      // Wipe out all fragments of that record 
      // https://www.algolia.com/doc/php#delete-by-query
      
      $client = new \AlgoliaSearch\Client($this->application_id, $this->api_key);
      $index = $client->initIndex($this->index);
      
      $res = $index->addObjects($records); 
      
      if(DEBUG) {
        \f::write($this->debug_path, '## Returned by Algolia', true);
        \f::write($this->debug_path, print_r($res, true), true);
      }
    }
    
  }

}

?>