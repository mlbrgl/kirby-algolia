<?php

namespace KirbyAlgolia;

class Fragment
{
  private $_id;
  private $_page_id;
  private $_heading;
  private $_content;
  private $_importance;
  private $_blueprint;
  private $_meta;

  public const PAGE_ID = "page_id";
  public const HEADING = "heading";
  public const CONTENT = "content";
  public const IMPORTANCE = "importance";
  public const BLUEPRINT = "blueprint";
  public const DATETIME = "datetime";

  /**
   * Sets the meta fields.
   *
   * Meta fields are the same for all fragments of a same page. They do not
   * create separate fragments but are rather attached to existing ones.
   *
   * @param      string  $field_name  The field name
   * @param      string  $value       The value
   */
  public function set_meta($field_name, $value)
  {
    $this->_meta[$field_name] = $value;
  }

  /**
   * Sets the fragment identifier.
   *
   * Format: [page_path]#[slugified_heading]--[fieldname][heading_count]
   *
   * @param      string  $value  The value
   */
  public function set_id($value)
  {
    $this->_id = $this->_page_id . "#" . $value;
  }

  /**
   * Sets the importance.
   *
   * The importance can be used in Algolia as a business metric. It is based on
   * the heading level. h1 -> importance : 1, h2 -> importance : 2, etc ...
   * https://blog.algolia.com/how-to-build-a-helpful-search-for-technical-documentation-the-laravel-example/
   *
   * @param      integer  $value  The value
   */
  public function set_importance($value)
  {
    $this->_importance = $value;
  }

  /**
   * Sets the content type.
   *
   * @param      <type>  $value  The value
   */
  public function set_blueprint($value)
  {
    $this->_blueprint = $value;
  }

  public function append_content($value)
  {
    $this->_content .= $value . PHP_EOL;
  }

  public function set_heading($value)
  {
    $this->_heading = $value;
  }

  public function get_content()
  {
    return $this->_content;
  }

  public function get_heading()
  {
    return $this->_heading;
  }

  /*
   * Prepare fragment before exporting
   */
  public function preprocess()
  {
    $this->_content = self::kirby_to_raw_text($this->_content);
    $this->_heading = self::kirby_to_raw_text($this->_heading);
  }

  public static function kirby_to_raw_text($kirbytext)
  {
    return trim(\Kirby\Toolkit\Html::decode(kirbytext($kirbytext)));
  }

  /*
   * Resets fragment content while preserving meta and blueprint fields
   */
  public function reset()
  {
    $this->_id = null;
    $this->_heading = null;
    $this->_importance = null;
    $this->_content = "";
  }

  /*
   * Sets the base identifier.
   *
   * @param      <type>  $page   The page
   *
   */
  public function set_page_id($page_id)
  {
    $this->_page_id = $page_id;
  }

  public function to_array()
  {
    $fragment = [];

    if (!empty($this->_id)) {
      $fragment["objectID"] = $this->_id;
    }

    if (!empty($this->_page_id)) {
      $fragment[self::PAGE_ID] = $this->_page_id;
    }

    if (!empty($this->_heading)) {
      $fragment[self::HEADING] = $this->_heading;
    }

    if (!empty($this->_content)) {
      $fragment[self::CONTENT] = $this->_content;
    }

    // '_importance' gets assigned 0 for boost fields, hence the different check
    if (isset($this->_importance)) {
      $fragment[self::IMPORTANCE] = $this->_importance;
    }

    if (!empty($this->_blueprint)) {
      $fragment[self::BLUEPRINT] = $this->_blueprint;
    }

    if (!empty($this->_meta)) {
      foreach ($this->_meta as $field => $value) {
        if (!empty($value)) {
          $fragment[$field] = $value;
        }
      }
    }

    return $fragment;
  }
}
