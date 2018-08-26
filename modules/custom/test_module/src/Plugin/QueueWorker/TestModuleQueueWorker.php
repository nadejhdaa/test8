<?php 
/**
 * @file
 * Contains \Drupal\test_module\Plugin\QueueWorker\TestModuleQueueWorker.
 */

namespace Drupal\test_module\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes tasks for test_module module.
 *
 * @QueueWorker(
 *   id = "test_module_queue",
 *   title = @Translation("Test module: Queue worker"),
 *   cron = {"time" = 10}
 * )
 */
class TestModuleQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $query = \Drupal::database()->insert('test_module');
      $query->fields([
        'title' => $item['Title'],
        'type' => $item['Type'],
        'price' => $item['Price'],
      ]);
      $query->execute(); 
  }

}