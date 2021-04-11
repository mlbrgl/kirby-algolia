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

class Parser
{
  private $settings = [];
  private $fragments = [];

  public function __construct($settings)
  {
    $this->settings = $settings;
  }

  public function parse($page)
  {
    $this->fragments = []; // reset internal array if parse() already called on this Parser instance
    $blueprint = $page->intendedTemplate()->name();
    $fields = $this->settings["fields"][$blueprint];
    $fragment = new Fragment();
    $fragment->set_base_id($page->id());

    // Meta fields are not being indexed separately but rather give context to the
    // main and boost fields. Since they do not change from fragment to fragment,
    // we set them once and for all.
    if (!empty($fields["meta"])) {
      foreach ($fields["meta"] as $meta_field) {
        // ATTENTION: VERY DIRTY HARDCODED TEMPORARY PREPROCESSING AROUND
        // PRESUPPOSED DATE FIELDS
        if ($meta_field == "datetime" || $meta_field == "date") {
          $fragment->set_meta($meta_field, $page->$meta_field()->toDate());
        } else {
          $fragment->set_meta($meta_field, $page->$meta_field()->value());
        }
      }
    }

    // The blueprint is the same for all fragments of the current article
    $fragment->set_blueprint($blueprint);

    // Boost fields
    if (!empty($fields["boost"])) {
      foreach ($fields["boost"] as $boost_field) {
        if (!empty($page->$boost_field()->value())) {
          $fragment->set_id($boost_field);
          $fragment->set_importance(0);
          $fragment->append_content($page->$boost_field()->value());

          $this->add_fragment($fragment);
        }
      }
    }

    // Main fields
    foreach ($fields["main"] as $main_field) {
      // heading_count is being used to uniquely identify a heading in the
      // content
      $heading_count = 0;

      if (!$page->$main_field()->isEmpty()) {
        // Initialisation for a possible headless fragment. If none are found, the
        // next add_fragment() will try adding an empty fragment (which does
        // nothing).
        $fragment->set_id($main_field);
        $fragment->set_importance(1);

        // Start breaking up the textarea line by line
        $line = strtok($page->$main_field(), PHP_EOL);
        while ($line !== false) {
          // A new heading has been found, a new fragment can be prepared.
          if (preg_match('/^(#+)\s+(.+)$/', $line, $matches)) {
            $heading_count++;

            $this->add_fragment($fragment);

            // Starting new heading based fragment
            $fragment->set_heading($matches[2]);
            $fragment->set_id(
              $main_field .
                "--" .
                \Kirby\Toolkit\Str::slug($matches[2]) .
                "--" .
                $heading_count
            );
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
        $this->add_fragment($fragment);
      }
    }
    return $this->fragments;
  }

  /*
   * Add a fragment to the internal array after pre-processing.
   *
   * @param      <type>  $fragment  The fragment
   */
  public function add_fragment($fragment)
  {
    // Prepares fragment for exporting
    $fragment->preprocess();

    $content = $fragment->get_content();
    $heading = $fragment->get_heading();

    // We only want fragments which contain at least one of these two fields: content or heading
    if (!empty($content) || !empty($heading)) {
      $this->fragments[] = $fragment->to_array();
    }

    $fragment->reset();
  }
}

?>
