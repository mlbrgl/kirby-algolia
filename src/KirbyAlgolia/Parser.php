<?php

/* 
line
line
--> headless fragment: discarding content between the beginning of the article
and the first heading. The resulting complexity of handling this
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

  public function __construct($index, $fields, $parsing_type = 'fragments'){
    $this->index = $index;
    $this->fields = $fields;
    $this->parsing_type = $parsing_type; // whether to segment the content in fragments
    
    // TODO raise an exception if $fields['main'] empty
  }

  public function parse($page) {
    switch ($this->parsing_type) {
      case 'fragments':
        $fragment = new Fragment();

        // Meta fields are not being indexed separately but rather give context to the
        // main and boost fields. Since they do not change from fragment to fragment,
        // we set them once and for all.
        if(!empty($this->fields['meta'])) {
          foreach($this->fields['meta'] as $meta_field) {
            // ATTENTION: VERY DIRTY HARDCODED TEMPORARY PREPROCESSING AROUND
            // PRESUPPOSED DATE FIELDS
            if($meta_field == 'datetime' || $meta_field == 'date') {
              $fragment->set_meta($meta_field, $page->date(null, $meta_field));
            } else {
              $fragment->set_meta($meta_field, $page->$meta_field()->value());
            }
          }
        }

        // Boost fields
        if(!empty($this->fields['boost'])) {
          foreach($this->fields['boost'] as $boost_field) {
            if(!empty($page->$boost_field()->value())) {
              $fragment->set_id(Fragment::get_base_id($page) . '#' . $boost_field);
              $fragment->set_importance(0);
              $fragment->append_content($page->$boost_field()->value());

              $fragment->preprocess();
              $this->index->add(array($fragment->to_array()));
            }
          }
        }

        // Main fields
        foreach($this->fields['main'] as $main_field) {
          // heading_count is being used to uniquely identify a heading in the 
          // content
          $heading_count = 0; 
          
          if(!$page->$main_field()->empty()) {
            // Start breaking up the textarea line by line
            $line = strtok($page->$main_field(), PHP_EOL);
            while ($line !== false) {
              
              // A new heading has been found, a new fragment can be prepared.
              if(preg_match('/^(#+)\s(\S.+)$/', $line, $matches)) {
                $heading_count ++;
                
                // Saving the previous fragment as record first, ignoring the first
                // match as it would either be a headless fragment or an empty one
                if($heading_count > 1) {
                  $fragment->preprocess();
                  $this->index->add(array($fragment->to_array()));
                }
                
                // Starting new heading based fragment
                $fragment->reset();
                $fragment->set_heading($matches[2]);
                $fragment->set_id(Fragment::get_base_id($page) 
                                       . '#' . \str::slug($matches[2]) 
                                       . '--' . $main_field . $heading_count);
                $fragment->set_importance(strlen($matches[1]));
              } else {
                // During the first runs before finding a heading in the current
                // main field, content from a potential headless fragment will be
                // appended here and then discarded by the above fragment reset,
                // without being used. TODO ? Do not append if heading empty?
                $fragment->append_content($line);
              }
              $line = strtok(PHP_EOL);
            }
            
            // Saving the last record (as saving only happens as a new heading
            // is found)
            $fragment->preprocess();
            $this->index->add(array($fragment->to_array()));
          }

        }     
        break;

      default:
        break;
    }
  }


}

?>