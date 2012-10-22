<?php defined('SYSPATH') OR die('No direct script access.');

class QDatabase_Driver_PgSQL extends QDatabase_Driver
{
  private $link = null;

  public function __construct($config)
  {
    $this->config = array_merge(
      array(
        'host' => 'localhost',
        'port' => 5432,
        'user' => '',
        'password' => '',
        'options' => array(),
      ), $config
    );
    // TODO
  }

  private function _connect()
  {
    if (is_resource($this->link)) {
      return false;
    }

    $connection_string = "host={$this->config['host']} port={$this->config['port']} dbname={$this->config['database']} user={$this->config['user']} password={$this->config['password']}";

    $this->link = @pg_connect($connection_string);
    if (!is_resource($this->link)) {
      throw new QDatabase_Exception('FIXME');
    }

    return true;
  }

  private function _disconnect()
  {
    if (is_resource($this->link)) {
      pg_close($this->link);
      $this->link = null;
      return true;
    }
    return false;
  }

  public function query($sql)
  {
    if (!$this->link) {
      $this->_connect();
    }
    return new QDatabase_Result_PgSQL($this->link, pg_query($this->link, $sql), $sql);
  }

  public function has_transactions()
  {
    return true;
  }

  public function begin_transaction()
  {
    $this->query("BEGIN");
  }

  public function commit_transaction()
  {
    $this->query("COMMIT");
  }

  public function rollback_transaction()
  {
    $this->query("ROLLBACK");
  }

  public function has_savepoints()
  {
    return true;
  }

  public function begin_savepoint($label = null)
  {
    $label = 'S'.uniqid();
    $this->query("SAVEPOINT {$label}");
    return $label;
  }

  public function commit_savepoint($label)
  {
    $this->query("RELEASE SAVEPOINT {$label}");
  }

  public function rollback_savepoint($label)
  {
    $this->query("ROLLBACK TO SAVEPOINT {$label}");
  }

  private function _escape_value($value, $type = null, $mode = 'simple')
  {
    if (!$this->link) {
      $this->_connect();
    }

    if ($value === null) {
      if ($mode == 'simple') {
        return 'NULL'.($type !== null ? '::'.$type : '');
      } elseif ($mode == 'array') {
        return 'NULL';
      } elseif ($mode == 'prepared') {
        return null;
      }
    } elseif ($type === null) {
      switch (gettype($value)) {
      case 'array':
        return $this->_escape_value($value[0], $value[1]);
        break;
      case 'boolean':
        return $value ? 'TRUE' : 'FALSE';
        break;
      case 'integer':
        return $value;
        break;
      case 'double':
        return $value;
        break;
      case 'string':
        return "'".pg_escape_string($this->link, $value)."'";
        break;
      default:
        // TODO
        break;
      }
    } else {
      if (substr($type, strlen($type) - 2, 2) == '[]') {
        $value = pg_escape_string($this->link, $this->_escape_array($value, substr($type, 0, -2)));
      } else {
        $base_type = $type;
        if (($pos = strpos('(', $type)) !== false) {
          $base_type = substr($type, 0, $pos);
        }
        switch ($base_type) {
        case 'int':						
        case 'bigint':
        case 'integer':
        case 'int4':
        case 'int8':
          $value = (int)$value;
          break;

        case 'bool':
        case 'boolean':
          $value = ((bool)$value ? 'TRUE' : 'FALSE');
          break;

        case 'varchar':
        case 'char':
        case 'text':
          $value = pg_escape_string($this->link, $value);
          break;

        case 'bytea':
          $value = pg_escape_bytea($this->link, $value);
          break;

        case 'hstore':
          $value = pg_escape_string($this->_escape_hstore($value));
          break;

        default:
          // TODO
        }
      }
      if ($mode == 'simple') {
        $value = "'{$value}'::{$type}";
      }
      return $value;
    }
  }

  public function escape_value($value, $type = null)
  {
    return $this->_escape_value($value, $type);
  }

  private function _escape_array(array $array, $type)
  {
    $elements = array();
    if (!empty($array)) {
      foreach ($array as $element) {
        if ($element === null) {
          $elements[] = 'NULL';
        } else {
          $element = $this->_escape_value($element, $type, 'array');
          $element = str_replace('"', '\"', $element);
          $element = str_replace('\\', '\\\\', $element);
          $elements[] = '"' . $element . '"';
        }
      }
    }
    return '{' . join(',', $elements) . '}';
  }

  private function _escape_hstore(array $hstore)
  {
    /* Kod z yoyo: do wyczyszczenia. */
    $hstore_escaped_splited = array();
    $hstore_escaped = '';
    if (is_array($hstore) && !empty($hstore)) {
      foreach ($hstore as $key => $value) {  
        if (is_array($value)) {
          throw new QDatabase_Exception('FIXME');
        }
        if ($value !== NULL) {
          if (is_bool($value)) {
            $value = (int)$value;
          }
          $value = str_replace('\\', '\\\\', $value);
          $value = str_replace('"', '\"', $value);
          $value = '"' . $value . '"';
        } else {
          $value = 'NULL';
        }
        $key = '"' . $key . '"';
        $hstore_escaped_splited[] = $key . '=>' . $value;
      }
      $hstore_escaped = join(",", $hstore_escaped_splited);
    }
    return $hstore_escaped;
  }
}

class QDatabase_Result_PgSQL extends QDatabase_Result
{


  public function __construct($link, $query, $sql, $params = array())
  {
    parent::__construct($link, $query, $sql, $params);
    $this->result_rows = pg_num_rows($this->query);
    $this->count = pg_affected_rows($this->query);
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

  public function result()
  {
    $result = pg_fetch_row($this->query, $this->current_position);
    $result_parsed = array();
    for ($column = 0; $column < $this->result_columns; $column++) {
      $result_parsed[$this->result_meta[$column]['name']] = $this->unescape($result[$column], $this->result_meta[$column]['type']);
    }
    return $result_parsed;
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
