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

  protected $count = 0;

  public function count()
  {
    return $this->count;
  }

  protected $result_rows = 0;
  protected $result_columns = 0;
  protected $result_meta = array();

  protected $current_position = 0;
  protected $current_result = null;

  public function current()
  {
    if (!$this->current_result) {
      $this->current_result = $this->result();
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

  abstract function result();
}

