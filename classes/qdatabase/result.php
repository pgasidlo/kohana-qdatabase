<?php defined('SYSPATH') OR die('No direct script access.');

abstract class QDatabase_Result implements Iterator
{
  protected $link = null;
  protected $query = null;

  public function __construct($link, $query, $sql, $params = array())
  {
    $this->link = $link;
    $this->query = $query;
  }
  
  public function count()
  {
    return $this->acount();
  }

  protected $affected_rows = 0;
  protected $result_rows = 0;

  public function acount()
  {
    return $this->affected_rows;
  }

  public function rcount()
  {
    return $this->result_rows;
  }

  protected $result_columns = 0;
  protected $result_meta = array();

  protected $current_position = 0;
  protected $current_result = null;

  public function current()
  {
    if (!$this->current_result) {
      if ($this->result_type === false) {
        $this->current_result = $this->result_array();
      } elseif (is_string($this->result_type)) {
        $this->current_result = $this->result_object($this->result_type);
      } elseif (is_array($this->result_type)) {
        $this->current_result = $this->result_object($this->result_type[0], $this->result_type[1]);
      } elseif ($this->result_type === true) {
        $this->current_result = $this->result_object();
      } else {
        throw new QDatabase_Exception('FIXME', 'FIXME');
      }
    }
    return $this->current_result;
  }

  public function key()
  {
    return $this->current_position;
  }


  public function next()
  {
    $this->current_position++;
    $this->current_result = null;
  }

  public function rewind()
  {
    $this->current_position = 0;
    $this->current_result = null;
  }

  public function valid()
  {
    return ($this->current_position < $this->result_rows);
  }

  private $result_type = false;

  public function result($result_type)
  {
    $this->result_type = $result_type;
    return $this;
  }

  abstract function result_array();
  abstract function result_object($className = null, array $classParams = null);
}

