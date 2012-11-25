<?php defined('SYSPATH') OR die('No direct script access.');

class QDatabase_Builder_PgSQL extends QDatabase_Builder
{
  public function sql()
  {    
    switch ($this->state['type']) {
    case self::TYPE_SELECT:
      return $this->_sql_select();
    case self::TYPE_INSERT:
      return $this->_sql_insert();
    case self::TYPE_UPDATE:
      return $this->_sql_update();
    case self::TYPE_DELETE:
      return $this->_sql_delete();
    default:
      throw new QDatabase_Exception('FIXME');
    }
  }

  private function _sql_select()
  {
    $sql = array();
    $sql[] = "SELECT";

    /* columns */
    if (!empty($this->state['columns'])) {
      $subsql = array();
      foreach ($this->state['columns'] as $as => $column) {
        if (is_numeric($as)) {
          $subsql[] = $column;
        } else {
          $subsql[] = "{$column} AS {$as}";
        }
      }
      $sql[] = implode(", ", $subsql);
      unset ($subsql);
    } else {
      $sql[] = '*';
    }

    /* from */
    if (!empty($this->state['from'])) {
      $sql[] = "FROM";
      $subsql = array();
      foreach ($this->state['from'] as $as => $table) {
        if (is_array($table)) {
          $table = call_user_func_array(array($this->qdb, 'sql'), $table);
        }
        if (is_numeric($as)) {
          $subsql[] = $table;
        } else {
          $subsql[] = "{$table} AS {$as}";
        }
      }
      $sql[] = implode(", ", $subsql);
      unset ($subsql);
    }

    /* join */
    if (!empty($this->state['join'])) {
      foreach ($this->state['join'] as $join) {
        $subsql = array();
        if (isset($join['type'])) {
          $subsql[] = "{$join['type']} JOIN";
        } else {
          $subsql[] = "JOIN";
        }
        if (is_array($join['table'])) {
          $table = call_user_func_array(array($this->qdb, 'sql'), $join['table']);
        }
        if ($join['table_alias'] === null) {
          $subsql[] = $join['table'];
        } else {
          $subsql[] = "{$join['table']} AS {$join['table_alias']}";
        }
        $sql[] = implode(" ", $subsql);
        $sql[] = "ON";
        $subsql = array();
        foreach ($join['on'] as $on) {
          if (is_array($on)) {
            $subsql[] = $this->qdb->sql($on[0], $on[1]);
          } else {
            $subsql[] = $on;
          }
        }
        $sql[] = implode(" AND ", $subsql);
        unset($subsql);
      }
    }

    /* where */
    if (!empty($this->state['where'])) {
      $sql[] = "WHERE";
      $subsql = array();
      foreach ($this->state['where'] as $where) {
        if (is_array($where)) {
          $subsql[] = $this->qdb->sql($where[0], $where[1]);
        } else {
          $subsql[] = $where;
        }
      }
      $sql[] = implode(" AND ", $subsql);
      unset ($subsql);
    }

    /* group */
    if (!empty($this->state['group'])) {
      $sql[] = "GROUP BY";
      $subsql = array();
      foreach ($this->state['group'] as $as => $column) {
        if (is_array($column)) {
          $column = call_user_func_array(array($this->qdb, 'sql'), $column);
        }
        if (is_numeric($as)) {
          $subsql[] = $column;
        } else {
          $subsql[] = "{$column} AS {$as}";
        }
      }
      $sql[] = implode(", ", $subsql);
      unset ($subsql);
    }

    /* having */
    if (!empty($this->state['having'])) {
      $sql[] = "HAVING";
      $subsql = array();
      foreach ($this->state['having'] as $having) {
        if (is_array($having)) {
          $subsql[] = $this->qdb->sql($having[0], $having[1]);
        } else {
          $subsql[] = $having;
        }
      }
      $sql[] = implode(" AND ", $subsql);
      unset ($subsql);
    }

    /* order */
    if (!empty($this->state['order'])) {
      $sql[] = "ORDER BY";
      $subsql = array();
      foreach ($this->state['order'] as $column => $order) {
        if (!is_numeric($column)) {
          $order = "{$column} {$order}";
        }
        if (is_array($order)) {
          $order = call_user_func_array(array($this->qdb, 'sql'), $order);
        }
        $subsql[] = $order;
      }
      $sql[] = implode(", ", $subsql);
      unset ($subsql);
    }

    /* limit */
    if ($this->state['limit'] !== null) {
      $sql[] = "LIMIT";
      $sql[] = (int)$this->state['limit'];
    }

    /* offset */
    if ($this->state['offset'] !== null) {
      $sql[] = "OFFSET";
      $sql[] = (int)$this->state['offset'];
    }

    $sql = implode(" ", $sql);
    $this->reset();

    return $sql;
  }

  private function _sql_insert()
  {
    $sql = array();
    $sql[] = "INSERT INTO";
    if (!isset($this->state['table'])) {
      throw new QDatabase_Exception('FIXME');
    }
    $sql[] = $this->state['table'];

    // values
    if (empty($this->set)) {
      $sql[] = "DEFAULT VALUES";
    } else {
      $sql[] = "(";
      $subsql = array();
      foreach ($this->set as $column => $value) {
        $subsql[] = $column;
      }
      $sql[] = implode(", ", $subsql);
      unset ($subsql);
      $sql[] = ") VALUES (";
      $subsql = array();
      foreach ($this->set as $column => $value) {
        $subsql[] = $this->qdb->escape_value($value);
      }
      $sql[] = implode(", ", $subsql);
      unset ($subsql);
      $sql[] = ")";
    }

    // returning
    if (!empty($this->state['columns'])) {
      $sql[] = "RETURNING";
      $subsql = array();
      foreach ($this->state['columns'] as $as => $column) {
        if (is_numeric($as)) {
          $subsql[] = $column;
        } else {
          $subsql[] = "{$column} AS {$as}";
        }
      }
      $sql[] = implode(", ", $subsql);
      unset ($subsql);
    }
    $sql = implode(" ", $sql);
    $this->reset();

    return $sql;
  }

  private function _sql_update()
  {
    $sql = array();
    $sql[] = "UPDATE";
    if (!isset($this->state['table'])) {
      throw new QDatabase_Exception('FIXME');
    }
    $sql[] = $this->state['table'];
    if (isset($this->state['table_alias'])) {
      $sql[] = "AS {$this->state['table_alias']}";
    }

    // set
    if (empty($this->set)) {
      throw new QDatabase_Exception('FIXME');
    }
    $sql[] = "SET";
    $subsql = array();
    foreach ($this->set as $column => $value) {
      $subsql[] = "{$column} = ".$this->qdb->escape_value($value);
    }
    $sql[] = implode(", ", $subsql);
    unset ($subsql);

    // from i join
    if (!empty($this->state['from']) || !empty($this->state['join'])) {
      $sql[] = "FROM";
      $subsql = array();
      foreach ($this->state['from'] as $as => $table) {
        if (is_array($table)) {
          $table = call_user_func_array(array($this->qdb, 'sql'), $table);
        }
        if (is_numeric($as)) {
          $subsql[] = $table;
        } else {
          $subsql[] = "{$table} AS {$as}";
        }
      }
      if (!empty($this->state['join'])) {
        foreach ($this->state['join'] as $join) {
          if (is_array($join['table'])) {
            $table = call_user_func_array(array($this->qdb, 'sql'), $join['table']);
          }
          if ($join['table_alias'] === null) {
            $subsql[] = $join['table'];
          } else {
            $subsql[] = "{$join['table']} AS {$join['table_alias']}";
          }
        }
      }

      $sql[] = implode(", ", $subsql);
      unset ($subsql);
    }
    

    // where
    if (!empty($this->state['where']) || !empty($this->state['join'])) {
      $sql[] = "WHERE";
      $subsql = array();
      foreach ($this->state['where'] as $where) {
        if (is_array($where)) {
          $subsql[] = $this->qdb->sql($where[0], $where[1]);
        } else {
          $subsql[] = $where;
        }
      }
      foreach ($this->state['join'] as $join) {
        foreach ($join['on'] as $where) {
          if (is_array($where)) {
            $subsql[] = $this->qdb->sql($where[0], $where[1]);
          } else {
            $subsql[] = $where;
          }
        }
      }
      $sql[] = implode(" AND ", $subsql);
      unset ($subsql);
    }

    // returning
    if (!empty($this->state['columns'])) {
      $sql[] = "RETURNING";
      $subsql = array();
      foreach ($this->state['columns'] as $as => $column) {
        if (is_numeric($as)) {
          $subsql[] = $column;
        } else {
          $subsql[] = "{$column} AS {$as}";
        }
      }
      $sql[] = implode(", ", $subsql);
      unset ($subsql);
    }
    $sql = implode(" ", $sql);
    $this->reset();

    return $sql;
  }

  private function _sql_delete()
  {
    $sql = array();
    $sql[] = "DELETE FROM";
    if (!isset($this->state['table'])) {
      throw new QDatabase_Exception('FIXME');
    }
    $sql[] = $this->state['table'];
    if (isset($this->state['table_alias'])) {
      $sql[] = "AS {$this->state['table_alias']}";
    }

    // where
    if (!empty($this->state['where'])) {
      $sql[] = "WHERE";
      $subsql = array();
      foreach ($this->state['where'] as $where) {
        if (is_array($where)) {
          $subsql[] = $this->qdb->sql($where[0], $where[1]);
        } else {
          $subsql[] = $where;
        }
      }
      $sql[] = implode(" AND ", $subsql);
      unset ($subsql);
    }

    // returning
    if (!empty($this->state['columns'])) {
      $sql[] = "RETURNING";
      $subsql = array();
      foreach ($this->state['columns'] as $as => $column) {
        if (is_numeric($as)) {
          $subsql[] = $column;
        } else {
          $subsql[] = "{$column} AS {$as}";
        }
      }
      $sql[] = implode(", ", $subsql);
      unset ($subsql);
    }
    $sql = implode(" ", $sql);
    $this->reset();

    return $sql;
  }
}

