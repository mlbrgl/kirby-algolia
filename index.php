<?php

require __DIR__ . "/vendor/autoload.php";

$settings = option("mlbrgl.kirby-algolia");

function is_page_indexable($page, $settings)
{
  return array_key_exists(
    $page->intendedTemplate()->name(),
    $settings["fields"]
  ) && $page->isListed();
}

$hook_create_page = function ($page) use ($settings) {
  if (is_page_indexable($page, $settings)) {
    kirby_algolia_create_update_page($page, $settings);
  }
};

$hook_update_page = function ($newPage, $oldPage) use ($settings) {
  if (is_page_indexable($oldPage, $settings)) {
    kirby_algolia_delete_page($oldPage, $settings);
  }
  if (is_page_indexable($newPage, $settings)) {
    kirby_algolia_create_update_page($newPage, $settings);
  }
};

$hook_delete_page = function ($staus, $page) use ($settings) {
  if (is_page_indexable($page, $settings)) {
    kirby_algolia_delete_page($page, $settings);
  }
};

\Kirby::plugin("mlbrgl/kirby-algolia", [
  "hooks" => [
    "page.create:after" => $hook_create_page,
    "page.update:after" => $hook_update_page,
    "page.changeTitle:after" => $hook_update_page,
    "page.changeStatus:after" => $hook_update_page,
    "page.changeSlug:after" => $hook_update_page,
    //"page.changeTemplate:after" => TODO
    //"page.changeNum:after" => TODO
    //"page.duplicate:after" => TODO
    "page.delete:after" => $hook_delete_page,
  ],
]);

/**
 * Master parsing and indexing function for a single page.
 *
 * @param      <type>  $page      The page being indexed
 * @param      <type>  $settings  The settings
 */
function kirby_algolia_create_update_page($page, $settings)
{
  $index = new \KirbyAlgolia\Index($settings);
  $parser = new \KirbyAlgolia\Parser($settings);

  $fragments = $parser->parse(
    $page,
    $settings["fields"][$page->intendedTemplate()->name()]
  );
  $index->create_update_fragments($page->id(), $fragments);
}

/**
 * Deletes records relating to a single page
 *
 * @param      <type>  $page      The page
 * @param      <type>  $settings  The settings
 */
function kirby_algolia_delete_page($page, $settings)
{
  $index = new \KirbyAlgolia\Index($settings);

  $index->delete_fragments($page->id());
}

?>