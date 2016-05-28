<?php



namespace KirbyAlgolia;

class Fragment {
  
  private $_id;
  private $_heading;
  public $_content;
  private $_importance;
  private $meta;

  public function __construct() {
        
  }


  /**
   * Sets the meta fields.
   *
   * Meta fields are the same for all fragments of a same page. They do not
   * create separate fragments but are rather attached to existing ones.
   *
   * @param      string  $field_name  The field name
   * @param      string  $value       The value
   */
  public function set_meta($field_name, $value) {
    $this->meta[$field_name] = $value;
  }


  /**
   * Sets the fragment identifier.
   *
   * Format: [page_path]#[slugified_heading]--[fieldname][heading_count]
   *
   * @param      string  $value  The value
   */
  public function set_id($value) {
    $this->_id = $value;
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
  public function set_importance($value) {
    $this->_importance = $value;
  }


  public function append_content($value) {
    $this->_content .= $value . PHP_EOL;
  }

  public function set_heading($value) {
    $this->_heading = $value;
  }


  /*
   * Run pre-process operations on a fragment
   */
  public function preprocess() {
    if(!empty($this->_content)) {
      $this->_content = \html::decode(kirbytext($this->_content));
    }
    if(!empty($this->_heading)) {
      $this->_heading = \html::decode(kirbytext($this->_heading));
    }
  }
  

  /*
   * Resets fragment content while preserving meta fields
   */
  public function reset() {
    unset($this->_id);
    unset($this->_heading);
    unset($this->_importance);
    // _content gets a special treatment as its content is being concatenated 
    // TODO necessary ?
    $this->_content = '';
  }

  /*
   *
   */
  public static function get_base_id($page) {
    return $page->id();
  }
 


  public function to_array() {
    $fragment = array();

    if(!empty($this->_id)) {
      $fragment['_id'] = $this->_id;
    }

    if(!empty($this->_heading)) {
      $fragment['_heading'] = $this->_heading;
    }

    if(!empty($this->_content)) {
      $fragment['_content'] = $this->_content;
    }

    // '_importance' gets assigned for boost fields, hence the different check
    if(isset($this->_importance)) {
      $fragment['_importance'] = $this->_importance;
    }

    if(!empty($this->meta)) {
      foreach($this->meta as $field => $value) {
        $fragment[$field] = $value;
      }
    }

    return $fragment;
  }



}
