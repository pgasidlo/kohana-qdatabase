<?php defined('SYSPATH') or die('No direct script access.');

class Controller_QDatabase extends Controller {

  public function action_index()
  {
    $this->qdb = QDatabase::instance();
    $query = $this->qdb->query("SET search_path = public,extensions");
    $this->qdb->begin();
    $this->qdb->begin();
    $query = $this->qdb->query(
      "SELECT :i AS i, :s AS s, :b AS b, :null AS null, :hs AS hs, :ia AS ia",
      array(
        ':i' => array(1, 'int'),
        ':s' => array('ALA', 'text'),
        ':b' => TRUE,
        ':null' => array(NULL, 'integer'),
        ':hs' => array(array('a' => 'b'), 'hstore'),
        ':ia' => array(array(1,2,3,NULL,5), 'int[]'),
      )
    );
    foreach ($query as $result) {
      var_dump($result);
    }
    $this->qdb->commit();
    $this->qdb->rollback();
    echo '<div id="kohana-profiler">';
    echo View::factory('profiler/stats');
    echo '</div>';
  }

}

