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

  /*
   * Updates records in the Algolia index by removing relevant records first.
   */
  public function create_update_fragments($page_id, $fragments)
  {
    if (empty($fragments) || empty($page_id)) {
      return;
    }

    // Sending indexing query to Algolia
    if ($this->active) {
      $this->algolia_index->saveObjects($fragments);
    }
  }

  // TODO
  // function update_batch() {
  //   case "batch":
  //     //
  //     // The index is cleared as the batch indexing process is blind and
  //     // does not keep track of what has been indexed or not. This is for
  //     // the moment the only way to avoid creating duplicates in the
  //     // index. Since the update function is called on every batch, we
  //     // only want to clear the index once, before sending the first batch
  //     static $index_cleared = false;
  //     if (!$index_cleared) {
  //       $index_cleared = true;
  //       $this->algolia_index->clearIndex();
  //     }
  // }

  /*
   * Removed all fragments of the same page.
   *
   * @param      string  $page_id  The fragments base identifier
   */
  public function delete_fragments($page_id)
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
