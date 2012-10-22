<?php defined('SYSPATH') OR die('No direct script access.');

abstract class QDatabase_Driver
{
  private $config = NULL;

  public function __construct($config)
  {
    $this->config = $config;
  }

  abstract public function query($sql);

  public function custom_functions()
  {
    return array();
  }

  public function has_transactions()
  {
    return false;
  }

  public function begin_transaction()
  {
    return false;
  }

  public function commit_transaction()
  {
    return false;
  }

  public function rollback_transaction()
  {
    return false;
  }

  public function has_savepoints()
  {
    return false;
  }

  public function begin_savepoint($label = null)
  {
    return false;
  }

  public function commit_savepoint($label)
  {
    return false;
  }

  public function rollback_savepoint($label)
  {
    return false;
  }

  abstract public function escape_value($value, $type = null);

  public function escape_column($column)
  {
    return $column;
  }

  public function escape_table($table)
  {
    return $table;
  }

  public function has_statements()
  {
    return false;
  }

  public function prepare_statement($sql)
  {
    return false;
  }

  public function execute_statement($stmt, $params = array())
  {
    return false;
  }

  public function deallocate_statement($stmt)
  {
    return false;
  }
}

