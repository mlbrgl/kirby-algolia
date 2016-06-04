<?php

namespace KirbyAlgolia;

class Index {

  private $settings;
  private $index;
  private $records;

  private $dry_run = FALSE;

  public function __construct($settings) {
    //TODO exception if missing elements
    $this->settings = $settings;

    if(!empty($settings['debug']) && in_array('dry_run', $settings['debug'])) {
      $this->dry_run = TRUE;
    } else {
      // Init Algolia's index
      $client = new \AlgoliaSearch\Client($settings['algolia']['application_id'], $settings['algolia']['api_key']);
      $this->index = $client->initIndex($settings['algolia']['index']);
    }
  }

  /*
   * Updates records in the Algolia index by removing relevant records first.
   *
   * @param      string  $type     'fragments'|'multiple'
   * @param      <type>  $options  'base_id'
   */
  public function update($type, $options = NULL) {
    if(!empty($this->records)){

      if(!$this->dry_run){
      
        switch ($type) {
          case 'fragments':
            // Before indexing new fragments, we need to remove all fragments of the same previously indexed content,
            // to prevent leaving ghost fragments in case a heading has been renamed. These fragments all share the 
            // same base id.
            if(!empty($options['base_id'])) {
              $this->delete_fragments($options['base_id']);
            }
            
            break;

          case 'batch':
            //
            // The index is cleared as the batch indexing process is blind and
            // does not keep track of what has been indexed or not. This is for
            // the moment the only way to avoid creating duplicates in the
            // index. Since the update function is called on every batch, we
            // only want to clear the index once, before sending the first batch
            static $index_cleared = FALSE;
            if(!$index_cleared) {
              $index_cleared = TRUE;
              $this->index->clearIndex();
            }

            break;

          default:
            break;
        }

        // Sending indexing query to Algolia
        $this->index->addObjects($this->records); 
      }
      // Resets the internal records array in preparation of the next call
      unset($this->records);
    }
    
  }

  /*
   * Add a fragment to the internal array for batch indexing.
   *
   * The resulting records will only be saved after a call to update().
   *
   * @param      <type>  $fragment  The fragment
   */
  public function add_fragment($fragment) {
    // Prepares fragment for exporting
    $fragment->preprocess();

    $content = $fragment->get_content();
    $heading = $fragment->get_heading();

	// We only want fragments which contain at least one of these two fields: content or heading
    if(!empty($content) || !empty($heading)) {
      $this->records[] = $fragment->to_array(); 
    }
  }

  /*
   * Removed all fragments sharing the base id (all paragraphs of the same
   * content)
   *
   * @param      string  $fragments_base_id  The fragments base identifier
   */
  public function delete_fragments($base_id) {
    if(!$this->dry_run) {
      $this->index->deleteByQuery($base_id, array('restrictSearchableAttributes' => '_id'));
    }
  }

}

?>