<?php

/* 
line
line
--> discarding content between the beginning of the article
and the first heading. The complexity of handling this
special case is not worth it at this stage.
# heading
line
--> sending off fragment
## heading
line
line
--> sending off fragment
# unlikely heading
--> sending off fragment by code convenience but very little value
*/

namespace KirbyAlgolia;


class Parser {

  private $fields;
  private $parsing_type;
  private $index;


  public function __construct($index, $fields, $parsing_type = 'fragment'){
    $this->index = $index;
    $this->fields = $fields;
    $this->parsing_type = $parsing_type; // whether to segment the content in fragments

    // TODO raise an exception if $fields['main'] empty
  }

  public function parse($page) {
    switch ($this->parsing_type) {
      case 'fragment':
        // Meta fields are not being indexed separately but rather give context to the
        // main and boost fields. Since they do not change from fragment to fragment,
        // we set them once and for all.
        $fragment = array();
        if(!empty($this->fields['meta'])) {
          foreach($this->fields['meta'] as $meta_field) {
            $fragment[$meta_field] = $page->$meta_field()->value();
          }
        }

        // Boost fields
        if(!empty($this->fields['boost'])) {
          foreach($this->fields['boost'] as $boost_field) {
            $fragment['_fragment_id'] = $page->id() . '#' . $boost_field;
            $fragment['_importance'] = 0;
            $fragment['_content'] = $page->$boost_field()->value();

            $this->_preprocess_fragment($fragment);
            $this->index->add(array($fragment));
          }
        }

        // Main fields
        // Blank slate (as the fragment is being reused for performance reasons)
        $this->_fragment_reset($fragment);

        foreach($this->fields['main'] as $main_field) {
          $heading_count = 0; // heading_count is being used to uniquely identify a heading in the content
          
          if(!$page->$main_field()->empty()) {
            // Start breaking up the textarea line by line
            $line = strtok($page->$main_field(), PHP_EOL);
            while ($line !== false) {
              
              // A new heading has been found, a new fragment can be prepared.
              if(preg_match('/^(#+)\s(.*)$/', $line, $matches)) {
                $heading_count ++;
                
                // Saving the previous fragment as record first, ignoring the first match
                // as it would either be a headless fragment or an empty one
                if($heading_count > 1) {
                  $this->_preprocess_fragment($fragment);
                  $this->index->add(array($fragment));
                }
                
                // Starting new heading based fragment
                $this->_fragment_reset($fragment);
                $fragment['_heading'] = $matches[2];
                $fragment['_fragment_id'] = $page->id() 
                                       . '#' . \str::slug($fragment['_heading']) 
                                       . '--' . $main_field . $heading_count;
                // The importance can be used in Algolia as a business metric. It is based on the heading
                // level. h1 -> importance : 1, h2 -> importance : 2, etc ...
                // https://blog.algolia.com/how-to-build-a-helpful-search-for-technical-documentation-the-laravel-example/
                $fragment['_importance'] = strlen($matches[1]);
              } else {
                $fragment['_content'] .= $line . PHP_EOL;
              }
              $line = strtok(PHP_EOL);
            }
            
            // Saving the last record (as saving only happens as a new heading is found)
            $this->_preprocess_fragment($fragment);
            $this->index->add(array($fragment));
          }

        }     
        break;

      default:
        break;
    }
  }


  /*
   * Resets fragment content while preserving meta fields
   */
  private function _fragment_reset(&$fragment) {
    unset($fragment['_fragment_id']);
    unset($fragment['_heading']);
    unset($fragment['_importance']);
    // _content gets a special treatment as its content is being concatenated 
    $fragment['_content'] = '';
  }


  /*
   * Run pre-process operations on a fragment
   */
  private function _preprocess_fragment(&$fragment) {
    if(!empty($fragment['_content'])) {
      $fragment['_content'] = \html::decode(kirbytext($fragment['_content']));
    }
  }
  
}

?>