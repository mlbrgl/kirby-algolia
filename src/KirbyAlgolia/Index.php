<?php

namespace KirbyAlgolia;

class Index
{
  private $algolia_index;
  private $active = false;

  public function __construct($settings)
  {
    //TODO exception if missing elements

    if (!$settings["active"]) {
      return;
    }
    $this->active = true;

    // Init Algolia's index
    $client = \Algolia\AlgoliaSearch\SearchClient::create(
      $settings["algolia"]["application_id"],
      $settings["algolia"]["api_key"]
    );
    $this->algolia_index = $client->initIndex($settings["algolia"]["index"]);
  }

  /**
   * Checks if the page is indexable (is listed and its blueprint is present in the configuration)
   *
   * @param \Kirby\Cms\Page $page
   * @param array $settings
   * @return boolean
   */
  public static function is_page_indexable($page, $settings)
  {
    return array_key_exists(
      $page->intendedTemplate()->name(),
      $settings["fields"]
    ) && $page->isListed();
  }

  /**
   * Sends fragments to Algolia for indexing
   *
   * @param Fragment $fragments
   * @return void
   */
  public function send_fragments_algolia($fragments)
  {
    if (empty($fragments)) {
      return;
    }

    if ($this->active) {
      $this->algolia_index->saveObjects($fragments);
    }
  }

  /**
   * Removed all fragments from the same page
   *
   * @param string $page_id
   * @return void
   */
  public function delete_fragments_algolia($page_id)
  {
    if (empty($page_id)) {
      return;
    }

    if ($this->active) {
      $this->algolia_index->deleteBy([
        "filters" => Fragment::PAGE_ID . ":" . $page_id,
      ]);
    }
  }
}

?>
