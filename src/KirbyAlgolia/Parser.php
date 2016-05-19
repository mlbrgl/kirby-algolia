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

  private $page;
  private $fields;
  private $parsing_type;
  //TOREVIEW better name as technically they are not recorded yet
  private $records; 

  //DEBUG
  // TODO option
  const DEBUG = 0;
  private $debug_path; 

  public function __construct($page, $fields, $parsing_type = 'fragment'){
    $this->page = $page;
    $this->fields = $fields;
    $this->parsing_type = $parsing_type; // whether to segment the content in fragments

    // TODO raise an exception if $fields['main'] empty

    if(self::DEBUG) {
      $this->debug_path = __DIR__ . '/parsed.txt';
      if(\f::exists($this->debug_path)){
        \f::remove($this->debug_path);
      }
    }
  }

  public function parse() {
    switch ($this->parsing_type) {
      case 'fragment':
        // Meta fields are not being indexed separately but rather give context to the
        // main and boost fields. Since they do not change from fragment to fragment,
        // we set them once and for all.
        $fragment = array();
        $this->_fragment_init($fragment);

        if(!empty($this->fields['boost'])) {
          foreach($this->fields['boost'] as $boost_field) {
            $fragment['_fragment_id'] = $this->page->id() . '#' . $boost_field;
            $fragment['_importance'] = 0;
            $fragment['_content'] = $this->page->$boost_field()->value();

            $this->preprocess_record($fragment);
            $this->add_record($fragment);
          }
        }

        // Blank slate. Enables correct detection of the first fragment below 
        // TOREVIEW improve performance by avoiding a full reload without compromising readability
        $this->_fragment_init($fragment);

        foreach($this->fields['main'] as $main_field) {
          $heading_count = 0; // heading_count is being used to uniquely identify a heading in the content
          
          if(!$this->page->$main_field()->empty()) {
            // Start breaking up the textarea line by line
            $line = strtok($this->page->$main_field(), PHP_EOL);
            while ($line !== false) {
              
              // A new heading has been found, a new fragment can be prepared.
              if(preg_match('/^(#+)\s(.*)$/', $line, $matches)) {
                $heading_count ++;
                
                // Saving the previous fragment as record first, ignoring the first empty initialized fragment
                if($heading_count != 1) {
                  $this->preprocess_record($fragment);
                  $this->add_record($fragment);
                }
                
                // Starting new fragment
                // TOREVIEW improve performance by avoiding a full reload without compromising readability
                $this->_fragment_init($fragment);
                $fragment['_heading'] = $matches[2];
                $fragment['_fragment_id'] = $this->page->id() 
                                       . '#' . \str::slug($fragment['_heading']) 
                                       . '--' . $main_field . $heading_count;
                $fragment['_content'] = '';
                // The importance can be used in Algolia as a business metric. It is based on the heading
                // level. h1 -> importance : 1, h2 -> importance : 2, etc ...
                // https://blog.algolia.com/how-to-build-a-helpful-search-for-technical-documentation-the-laravel-example/
                $fragment['_importance'] = strlen($matches[1]);
              } else {
                $fragment['_content'] .= $line . PHP_EOL;
              }
              $line = strtok(PHP_EOL);
            }
            
            // Saving the last record (as saving would only happens as a new heading is found)
            $this->preprocess_record($fragment);
            $this->add_record($fragment);
          }

        }     
        break;

      default:
        break;
    }
    return $this->get_records();
  }

  // TODO : helper function to be moved ?
  // Initialize new fragment. Reserved keys are:
  // - ObjectID
  // - _heading
  // - _content
  // - _importance
  private function _fragment_init(&$fragment) {
    
    $fragment = array();

    if(!empty($this->fields['meta'])) {
      foreach($this->fields['meta'] as $meta_field) {
        $fragment[$meta_field] = $this->page->$meta_field()->value();
      }
    }
  }


  /*
   * Runs through pro-process operations on record
   */
  private function preprocess_record(&$record) {
    if(!empty($record['_content'])) {
      $record['_content'] = \html::decode(kirbytext($record['_content']));
    }
  }


  /*
   * Add a record to the array of records held by the parser
   */
  public function add_record($record) {
    if(self::DEBUG) {
      \f::write($this->debug_path, '## Adding a new record' . PHP_EOL . print_r($record, true), true);
    }
    
    if(!empty($record)){
      $this->records[] = $record;
    }
  }


  /*
   * Returns the records held by the parser
   */
  public function get_records() {
    if(self::DEBUG) {
      \f::write($this->debug_path, '## All parsed records' . PHP_EOL . print_r($this->records, true), true);
    }
    return $this->records;
  }
  
}

?>