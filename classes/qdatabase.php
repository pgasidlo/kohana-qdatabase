<?php defined('SYSPATH') OR die('No direct script access.');

class QDatabase
{
  public static $default = 'default';
  public static $instances = array();
  private $name = null;

  private $driver = null;

  public function __construct($name = null, array $config = null)
  {
    $this->name = $name;
    if ($config === null) {
      $config = Kohana::$config->load('qdatabase')->$name;
    }

    if (!isset($config['type'])) {
      throw new Kohana_Exception('QDatabase type not defined in :name configuration', array(':name' => $name));
    }

    $driver = 'QDatabase_Driver_'.ucfirst($config['type']);

    $this->driver = new $driver($config);
  }

  public static function instance($name = null, array $config = null)
  {
    if ($name === null) {
      $name = QDatabase::$default;
    }

    if (!isset(QDatabase::$instances[$name])) {
      QDatabase::$instances[$name] = new QDatabase($name, $config);
    }

    return QDatabase::$instances[$name];
  }

  /* Transactions */

  private $transactions_stack = array();

  public function begin($label = null)
  {
    if (!$this->driver->has_transactions()) {
      throw new QDatabase_Exception('FIXME');
    }
    if (!empty($this->transactions_stack)) {
      if (!$this->driver->has_savepoints()) {
        throw new QDatabase_Exception('FIXME');
      } else {
        $this->transactions_stack[] = array('label' => $label, 'internal_label' => $this->driver->begin_savepoint($label));
      }
    } else {
      $this->driver->begin_transaction();
      $this->transactions_stack[] = array('label' => $label);
    }
  }

  public function commit($label = null)
  {
    if (!$this->driver->has_transactions()) {
      throw new QDatabase_Exception('FIXME');
    }
    if (empty($this->transactions_stack)) {
      throw new QDatabase_Exception('FIXME');
    }
    if ($label === true) {
      $this->driver->commit_transaction();
      $this->transactions_stack = array();
      return;
    } else {
      while ($transaction = array_pop($this->transactions_stack)) {
        if ($transaction['label'] === $label) {
          if (!empty($this->transactions_stack)) {
            $this->driver->commit_savepoint($transaction['internal_label']);
          } else {
            $this->driver->commit_transaction();
          }
          return;
        }
      }
    }
    throw new QDatabase_Exception('FIXME');
  }

  public function rollback($label = null)
  {
    if (!$this->driver->has_transactions()) {
      throw new QDatabase_Exception('FIXME');
    }
    if (empty($this->transactions_stack)) {
      throw new QDatabase_Exception('FIXME');
    }
    if ($label === true) {
      $this->driver->rollback_transaction();
      $this->transactions_stack = array();
      return;
    } else {
      while ($transaction = array_pop($this->transactions_stack)) {
        if ($transaction['label'] === $label) {
          if (!empty($this->transactions_stack)) {
            $this->driver->rollback_savepoint($transaction['internal_label']);
          } else {
            $this->driver->rollback_transaction();
          }
          return;
        }
      }
    }
    throw new QDatabase_Exception('FIXME');
  }

  /* Queries */

  public function query($sql, $params = array())
  {
    return $this->driver->query($this->sql($sql, $params));
  }

  public function sql($sql, $params = array())
  {
    $params_escape = array();
    $params_noescape = array();

    foreach ($params as $key => $param) {
      if ($key[0] == ':') {
        $params_escape[$key] = $param;
      } elseif ($key[0] == '#') {
        $params_noescape[$key] = $param;
      } else {
        throw new QDatabase_Exception('FIXME');
      }
    }

    $params_escape = array_map(array($this->driver, 'escape_value'), $params_escape);

    return strtr($sql, $params_escape + $params_noescape);
  }

  /* Prepared statements */

  private $statements = array();

  public function prepare($label, $sql)
  {
    if (!$this->driver->has_statements()) {
      throw new QDatabase_Exception('FIXME');
    }
    if (isset($this->statements[$label])) {
      throw new QDatabase_Exception('FIXME');
    }
    $this->statements[$label] = $this->driver->prepare_statement($sql);
    return true;
  }

  public function execute($label, $params = array())
  {
    if (!$this->driver->has_statements()) {
      throw new QDatabase_Exception('FIXME');
    }
    if (!isset($this->statements[$label])) {
      throw new QDatabase_Exception('FIXME');
    }
    $stmt = $this->statements[$label];
    return $this->driver->statement_execute($stmt, $params);
  }

  public function deallocate($label)
  {
    if (!$this->driver->has_statements()) {
      throw new QDatabase_Exception('FIXME');
    }
    if (!isset($this->statements[$label])) {
      throw new QDatabase_Exception('FIXME');
    }
    $stmt = $this->statements[$label];
    $this->driver->statement_deallocate($stmt);
    unset ($this->statements[$label]);
    return true;
  }

  /* Escaping */

  public function escape_value($value, $type = null)
  {
    if (is_array($value) && $type === null) {
      return $this->driver->escape_value($value[0], $value[1]);
    } else {
      return $this->driver->escape_value($value, $type);
    }
  }

  /* Custom functions */
  public function __call($method, $args)
  {
    if (in_array($method, $this->driver->custom_functions())) {
      return call_user_func_array($method, $args);
    }
    throw new QDatabase_Exception('FIXME');
  }
}
