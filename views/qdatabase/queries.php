<?php defined('SYSPATH') or die('No direct script access.') ?>

<style type="text/css">
<?php include Kohana::find_file('views', 'qdatabase/style', 'css') ?>
</style>

<?php $totals = array(); ?>
<?php $all = array(); ?>
<div class="qdatabase">
  <table class="queries">
    <thead>
      <tr class="header">
        <th class="left" colspan="6">
          QDatabase
        </th>
      </tr>
      <tr class="subheader">
        <th rowspan="2" class="left">Instance</th>
        <th rowspan="2" class="left">Transaction<br/>depth</th>
        <th rowspan="2" class="left">SQL</th>
        <th rowspan="2" class="right">Time</th>
        <th colspan="2" class="center">Rows</th>
      </tr>
      <tr class="subheader">
        <th class="right">Result</th>
        <th class="right">Affected</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (QDatabase::$queries as $query): ?>
      <tr class="<?php echo $query['type'] ?>">
        <td class="center">
          <?php echo $query['instance'] ?>
        </td>
        <td class="center">
          <?php echo @$query['transaction_depth'] ?>
        </td>
        <td class="left sql">
          <?php echo nl2br($query['sql']) ?>
        </td>
        <td class="right">
          <?php echo $query['time'] ?>
          <?php $totals[$query['instance']]['time'] = @$totals[$query['instance']]['time'] + $query['time'] ?>
        </td>
        <td class="right">
          <?php echo $query['rcount'] ?>
          <?php $totals[$query['instance']]['rcount'] = @$totals[$query['instance']]['rcount'] + $query['rcount'] ?>
        </td>
        <td class="right">
          <?php echo $query['acount'] ?>
          <?php $totals[$query['instance']]['acount'] = @$totals[$query['instance']]['acount'] + $query['acount'] ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <?php $all = array('time' => 0, 'rcount' => 0, 'acount' => 0); ?>
      <?php foreach ($totals as $instance => $total): ?>
      <tr>
        <td class="right" colspan="3">
          <?php echo $instance ?>
        </td>
        <td class="right">
          <?php echo $total['time'] ?>
          <?php $all['time'] = @$all['time'] + $total['time'] ?>
        </td>
        <td class="right">
          <?php echo $total['rcount'] ?>
          <?php $all['rcount'] = @$all['rcount'] + $total['rcount'] ?>
        </td>
        <td class="right">
          <?php echo $total['acount'] ?>
          <?php $all['acount'] = @$all['acount'] + $total['acount'] ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr>
        <td class="right" colspan="3">
          ALL
        </td>
        <td class="right">
          <?php echo $all['time'] ?>
        </td>
        <td class="right">
          <?php echo $all['rcount'] ?>
        </td>
        <td class="right">
          <?php echo $all['acount'] ?>
        </td>
    </tfoot>
  </table>
</div>
