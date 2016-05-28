<?php

require __DIR__ . '/vendor/autoload.php';


// Registering page hide hook (triggered when hiding a page) 
kirby()->hook('panel.page.hide', function($page) {
  $settings = c::get('kirby-algolia');
  kirby_algolia_delete($page, $settings);
});


// Registering page delete hook
kirby()->hook('panel.page.delete', function($page) {
  $settings = c::get('kirby-algolia');
  kirby_algolia_delete($page, $settings);
});


// Registering page sort hook (triggered when sorting or making a page visible)
// TODO : do not index when just sorting as opposed to making visible
kirby()->hook('panel.page.sort', function($page) {
  $settings = c::get('kirby-algolia');
  if(in_array($page->template(), $settings['content']['types'])) {
    kirby_algolia_update_index($page, $settings);
  }
});


// Registering page move hook
kirby()->hook('panel.page.move', function($page, $old_page) {
  $settings = c::get('kirby-algolia');
  if($page->isVisible() && in_array($page->template(), $settings['content']['types'])) {
    kirby_algolia_delete($old_page, $settings);
    kirby_algolia_update_index($page, $settings);
  }
});


// Registering page update hook
kirby()->hook('panel.page.update', function($page) {
  $settings = c::get('kirby-algolia');
  // Only interested in indexing visible pages, which are set in the config
  if($page->isVisible() && in_array($page->template(), $settings['content']['types'])) {
    kirby_algolia_update_index($page, $settings);
  }
});


/**
 * Master parsing and indexing function for a single page.
 *
 * (only supports fragment indexing at the moment)
 *
 * @param      <type>  $page      The page being indexed
 * @param      <type>  $settings  The settings
 */
function kirby_algolia_update_index($page, $settings) {
  $index = new \KirbyAlgolia\Index($settings);
  $parser = new \KirbyAlgolia\Parser($index, $settings['fields'], 'fragments');
  
  $parser->parse($page);
  $index->update('fragments', array('base_id' => \KirbyAlgolia\Fragment::get_base_id($page)));
}


/**
 * Deletes records relating to a single page
 *
 * @param      <type>  $page      The page
 * @param      <type>  $settings  The settings
 */
function kirby_algolia_delete($page, $settings) {
  $index = new \KirbyAlgolia\Index($settings);

  $index->delete_fragments(\KirbyAlgolia\Fragment::get_base_id($page));
}

?>