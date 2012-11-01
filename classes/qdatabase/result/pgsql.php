<?php defined('SYSPATH') OR die('No direct script access.');

class QDatabase_Result_PgSQL extends QDatabase_Result
{
  public function __construct($link, $query, $sql, $params = array())
  {
    parent::__construct($link, $query, $sql, $params);
    $this->result_rows = pg_num_rows($this->query);
    $this->affected_rows = pg_affected_rows($this->query);
    $this->result_columns = pg_num_fields($this->query);
    for ($column = 0; $column < $this->result_columns; $column++) {
      $this->result_meta[$column] = array(
        'name' => pg_field_name($this->query, $column),
        'size' => pg_field_size($this->query, $column),
        'length' =>  pg_field_prtlen($this->query, $column),
        'type' => pg_field_type($this->query, $column),
      );
    }
  }

  public function result_array()
  {
    $result = pg_fetch_row($this->query, $this->current_position);
    $result_parsed = array();
    for ($column = 0; $column < $this->result_columns; $column++) {
      $result_parsed[$this->result_meta[$column]['name']] = $this->unescape($result[$column], $this->result_meta[$column]['type']);
    }
    return $result_parsed;
  }

  public function result_object($className = null, array $classParams = null)
  {
    if ($className !== null) {
      return pg_fetch_object($this->query, $this->current_position, $className, $classParams);
    } else {
      return pg_fetch_object($this->query, $this->current_position);
    }
  }

  public function unescape($value, $type)
  {
    if ($type[0] == '_') {
      $type = substr($type, 1);
      return $this->unescape_array($value, $type);
    }
    if ($value === null) {
      return $value;
    }
    switch ($type) {
    case 'int2':
    case 'int4':
    case 'int8':
      $value = (int)$value;
      break;
    case 'float4':
    case 'float8':
      $value = (float)$value;
      break;
    case 'bool':
      $value = ($value == 't');
      break;
    case 'bytea':
      $value = pg_unescape_bytea($value);
      break;
    case 'hstore':
      $value = $this->unescape_hstore($value);
      break;
    }
    return $value;
  }

  private function unescape_hstore($hstore)
  {
    $hstore = preg_replace('/([$])/', "\\\\$1", $hstore);
    $hstore_parsed = array();
    eval('$hstore_parsed = array(' . $hstore . ');');
    return $hstore_parsed;
  }

  public function unescape_array($array, $type)
  {
    if (is_null($array)) {
      return null;
    }
    if ($array == "{}") {
      return array();
    };
    $array = preg_replace('/^{(.*)}$/', '\1', $array);    
    /* 
     * Rozbicie tablicy na tokeny:
     * - ciąg znaków nie będący spacją, cudzysłowiem, przecikiem
     * - ciąg znaków zaczynący sie od ", poźniej (dowolny znak różny od " i \) lub (\\) lub (\"), później " 
     */
    if (preg_match_all('/(?:([^ ",]+)|(?:"((?:[^"\\\\]|\\\\\\\\|\\\\")+?)"))/', $array, $element_tokens, PREG_SET_ORDER)) {
      $elements = array();
      foreach ($element_tokens as $element_token) {
        if (isset($element_token[2])) {
          $element = $element_token[2];
          $element = str_replace('\\\\', '\\', $element);
          $element = str_replace('\\"', '"', $element);
        } else {
          $element = $element_token[1];
        }
        if ($element == 'NULL') {
          $elements[] = null;
        } else {
          $elements[] = $this->unescape($element, $type);
        }
      }  
      return $elements;
    } else {
      throw new QDatabase_Exception('FIXME');
    }
  }

}

