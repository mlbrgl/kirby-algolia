<?php

/* 
line
line
--> INDEXING (headless fragment)
# heading
line
--> INDEXING
## heading
--> INDEXING (content-less heading)
### subheading
line
line
--> INDEXING
# unlikely heading
--> INDEXING by code convenience but very little value
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

              $this->index->add_fragment($fragment);
            }
          }
        }


        // Main fields
        foreach($this->fields['main'] as $main_field) {
          // heading_count is being used to uniquely identify a heading in the 
          // content
          $heading_count = 0; 
          
          if(!$page->$main_field()->empty()) {
            // Resetting fragment before processing main field
            $fragment->reset();
            // Init fragment ID in case the content starts with a headless fragment
            $fragment->set_id(Fragment::get_base_id($page) 
                             . '#' . $main_field);

            // Start breaking up the textarea line by line
            $line = strtok($page->$main_field(), PHP_EOL);
            while ($line !== false) {
              
              // A new heading has been found, a new fragment can be prepared.
              if(preg_match('/^(#+)\s+(.+)$/', $line, $matches)) {
                $heading_count ++;
                
                $this->index->add_fragment($fragment);
                                
                // Starting new heading based fragment
                $fragment->reset();
                $fragment->set_heading($matches[2]);
                $fragment->set_id(Fragment::get_base_id($page) 
                                 . '#' . $main_field 
                                 . '--' . \str::slug($matches[2]) 
                                 . '--' . $heading_count);
                $fragment->set_importance(strlen($matches[1]));
              } else {
                $fragment->append_content($line);
              }
              // NB: two PHP_EOL (i.e. two new lines) without a character or
              // string between them will be considered as one by strtok().
              // Effectively, this means that empty lines do not make it to the
              // final content.
              $line = strtok(PHP_EOL);
            }
            
            // Saving the last fragment (as saving only happens as a new heading
            // is found). This also takes care of heading-less articles.
            $this->index->add_fragment($fragment);
          }

        }     
        break;

      default:
        break;
    }
  }


}

?>