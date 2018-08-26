<?php 

namespace Drupal\test_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
 
/**
 * Provides route responses for the Example module.
 */
class TestModuleProducts extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function content() {

    $header = array(
      'title' => array(
        'data' => t('Title'),
        'field' => 'title',
        'sort' => 'asc'
      ),
    
      'type' => array(
        'data' => t('Type'),
        'field' => 'type',
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),

      'changed' => array(
        'data' => t('Price'),
        'field' => 'price',
        'sort' => 'desc',
        'class' => array(RESPONSIVE_PRIORITY_LOW)
      )
    
    );

    $db = \Drupal::database();
    $query = $db->select('test_module','t');
    $query->fields('t');
    // The actual action of sorting the rows is here.
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
                        ->orderByHeader($header);
    // Limit the rows to 20 for each page.
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
                        ->limit(20);
    $result = $pager->execute();
 
    // Populate the rows.
    $rows = array();
    foreach($result as $row) {
      $class = $row->price >= 0 ? 'ok' : 'attention';
      $rows[] = array(
        'data' => array(
          'name' => $row->title,
          'type' => $row->type, 
          'price' => $row->price
        ),
        'class' => [$class],
      );
    }
 

   $build = array(
      '#markup' => t('List of all products')
    );
 
    // Generate the table.
    $build['config_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
 
    // Finally add the pager.
    $build['pager'] = array(
      '#type' => 'pager'
    );
 
    return $build;
  } 
}
