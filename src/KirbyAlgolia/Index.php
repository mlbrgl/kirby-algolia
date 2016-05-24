<?php

namespace KirbyAlgolia;

class Index {

  private $application_id;
  private $index;
  private $api_key;
  private $records;

  //TODO option
  const ENABLE_INDEXING = 1;

  public function __construct($algolia_settings) {
    //TODO exception
    if(  !empty($algolia_settings['application_id'])
      && !empty($algolia_settings['index'])
      && !empty($algolia_settings['api_key'])) {
    
      $this->application_id = $algolia_settings['application_id'];
      $this->index = $algolia_settings['index'];
      $this->api_key = $algolia_settings['api_key'];
    }

  }

  /*
   * Saves (sends) records to the Algolia index
   */
  public function save($indexing_type, $fragments_shared_root = NULL) {
    if(!empty($this->records)){

      if(self::ENABLE_INDEXING){        
      
        // Init Algolia's index
        $client = new \AlgoliaSearch\Client($this->application_id, $this->api_key);
        $index = $client->initIndex($this->index);

        switch ($indexing_type) {
          case 'fragment':
            // Before indexing new fragments, we need to remove all fragments of the same previously indexed content,
            // to prevent leaving ghost fragments in case a heading has been renamed. These fragments all share the 
            // same root.
            $index->deleteByQuery($fragments_shared_root, array('restrictSearchableAttributes' => '_fragment_id'));
            
            break;

          case 'multiple':
            // The index is cleared as the batch indexing process is blind and does not
            // keep track of what has been indexed or not. This is for the moment the only
            // way to avoid creating duplicates in the index.
            $index->clearIndex();

            break;

          default:
            break;
        }

        // Sending indexing query to Algolia
        $res = $index->addObjects($this->records); 
      }
      
    }
    
  }

  /*
   * Add records to the internal array for batch indexing
   */
  public function add($records) {
    foreach($records as $record) {
      $this->records[] = $record; 
    }
  }


}

?>