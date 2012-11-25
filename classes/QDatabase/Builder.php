<?php defined('SYSPATH') OR die('No direct script access.');

abstract class QDatabase_Builder
{
  const TYPE_SELECT = 0;
  const TYPE_INSERT = 1;
  const TYPE_UPDATE = 2;
  const TYPE_DELETE = 3;

  protected $qdb = null;

  public function __construct($qdb)
  {
    $this->qdb = $qdb;
  }

  protected $state = array();

  public function reset($type = null)
  {
    $this->state = array(
      'type' => $type,
      'columns' => array(),
      'table' => null,
      'table_alias' => null,
      'from' => array(),
      'join' => array(),
      'where' => array(),
      'group' => array(),
      'having' => array(),
      'order' => array(),
      'limit' => null,
      'offset' => null,
      'set' => array(),
    );
  }

  public function insert($table)
  {
    $this->reset(self::TYPE_INSERT);
    $this->state['table'] = $table;
    return $this;
  }

  public function update($table, $table_alias = null)
  {
    $this->reset(self::TYPE_UPDATE);
    $this->state['table'] = $table;
    $this->state['table_alias'] = $table_alias;
    return $this;
  }

  public function delete($table, $table_alias = null)
  {
    $this->reset(self::TYPE_DELETE);
    $this->state['table'] = $table;
    $this->state['table_alias'] = $table_alias;
    return $this;
  }

  public function select($table = null, $table_alias = null)
  {
    $this->reset(self::TYPE_SELECT);
    if ($table_alias === null) {
      $this->state['from'][] = $table;
    } else {
      $this->state['from'][$table_alias] = $table;
    }
    return $this;
  }

  /**
   * columns('col1')
   * columns('col1', 'c1')
   * columns(array('c1' => 'col1', 'col2')
   */
  public function columns($arg)
  {
    $args = func_get_args();
    if (is_array($args[0])) {
      $this->state['columns'] = array_merge($this->state['columns'], $args[0]);
    } elseif (count($args) == 1) {
      $this->state['columns'][] = $args[0];
    } else {
      $this->state['columns'][$args[1]] = $args[0];
    }
    return $this;
  }

  /**
   * from('table1')
   * from('table1', 't1')
   * from(array('t1' => 'table1', 'table2'))
   */
  public function from($table, $table_alias = null)
  {
    if (is_array($table)) {
      $this->state['from'] = array_merge($this->state['from'], $table);
    } elseif ($table_alias !== null) {
      $this->state['from'][$table_alias] = $table;
    } else {
      $this->state['from'][] = $table;
    }
    return $this;
  }

  public function join($table, $table_alias = null, $on = array(), $type = null)
  {
    $this->state['join'][] = array(
      'table' => $table,
      'table_alias' => $table_alias,
      'on' => $on,
      'type' => $type,
    );
    return $this;
  }

  /**
   * where('col1 = 1')
   * where('col1', 1)
   * where('col1', 'LIKE', '%col1%')
   */
  public function where($arg)
  {
    $args = func_get_args();
    if (count($args) == 1) {
      $this->state['where'][] = $args[0];
    } elseif (count($args) == 2) {
      $this->state['where'][] = array(
        "{$args[0]} = :arg",
        array(
          ':arg' => $args[1]
        )
      );
    } elseif (count($args) == 3) {
      $this->state['where'][] = array(
        "{$args[0]} {$args[1]} :arg",
        array(
          ':arg' => $args[2]
        )
      );
    }
    return $this;
  }

  /**
   * group('column1')
   * group('column1', 'column2')
   * group(array('column1', 'column2'))
   */
  public function group($arg)
  {
    $args = func_get_args();
    if (is_array($args[0])) {
      $this->state['group'] = array_merge($this->state['group'], array_values($args[0]));
    } else {
      $this->state['group'] = array_merge($this->state['group'], array_values($args));
    }
    return $this;
  }

  /**
   * having('col1 = 1')
   * having('col1', 1)
   * having('col1', 'LIKE', '%col1%')
   */
  public function having($arg)
  {
    $args = func_get_args();
    if (count($args) == 1) {
      $this->state['having'][] = $args[0];
    } elseif (count($args) == 2) {
      $having[] = array(
        "{$args[0]} = ?",
        array(
          $args[1]
        )
      );
    } elseif (count($args) == 3) {
      $this->state['having'][] = array(
        "{$args[0]} {$args[1]} ?",
        array(
          $args[2]
        )
      );
    }
    return $this;
  }

  public function order($arg)
  {
    $args = func_get_args();
    if (is_array($args[0])) {
      $this->state['order'] = array_merge($this->state['order'], $args[0]);
    } elseif (count($args) == 1) {
      $this->state['order'][] = $args[0];
    } elseif (count($args) == 2) {
      $this->state['order'][] = "{$args[0]} {$args[1]}";
    }
    return $this;
  }

  public function limit($limit)
  {
    $this->state['limit'] = $limit;
    return $this;
  }

  public function offset($offset)
  {
    $this->state['offset'] = $offset;
    return $this;
  }

  /**
   * set('column', 'value')
   * set('column2 = 2')
   * set(array('column1' => 'value1', 'column2 = 2'))
   */
  public function set($arg)
  {
    $args = func_get_args();
    if (is_array($args[0])) {
      $this->set = array_merge($this->set, $args[0]);
    } elseif (count($args) == 1) {
      $this->set[] = $args[0];
    } else {
      $this->set[$args[0]] = $args[1];
    }
    return $this;
  }

  abstract public function sql();

  public function query()
  {
    $sql = $this->sql();
    return $this->qdb->query($sql);
  }
}

