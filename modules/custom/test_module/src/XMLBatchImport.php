<?php

namespace Drupal\test_module;

// We use File functions
use Drupal\file\Entity\File;

/**
 * Class CSVBatchImport.
 *
 * @package Drupal\test_module
 */
class XMLBatchImport {

  # Our Batch operations
  private $batch;

  # FID for xml file.
  private $fid;

  # File object.
  private $file;

  /**
   * {@inheritdoc}
   */
  public function __construct($fid, $batch_name = 'Custom XML import') {
    $this->fid = $fid;
    $this->file = File::load($fid);
    $this->batch = [
      'title' => $batch_name,
      'finished' => [$this, 'finished'],
      'file' => drupal_get_path('module', 'test_module') . '/src/XMLBatchImport.php',
    ];
    $this->parseXML();
  }

  /**
   * {@inheritdoc}
   *
   * parse XML and chunk data to force performance
   */
  public function parseXML() {  

    // load XML file
    $file = File::load($this->fid);

    // if XML file exists
    if($file) {
      // Clear DB table from old records before import 
      $query = \Drupal::database()->delete('test_module');
      $query->execute();

      // load xml file and get its data in array
      $filepath = drupal_realpath($file->getFileUri());
      $xml = simplexml_load_file($filepath); 
      $json = json_encode($xml);
      $data =  json_decode($json,true);

      // Split array to chunks to lighter performance of batch
      $chunks = array_chunk($data['Product'], 100);
      foreach ($chunks as $chunk) {  
        foreach ($chunk as $row) {
          $this->setOperation($row); 
        } 
      }
    }

  }

  /**
   * {@inheritdoc}
   *
   * Every row is array from product data (title, type, price). 
   * Send it to batch operation item
   */
  public function setOperation($row) { 
    $this->batch['operations'][] = [[$this, 'processItem'], $row];
  }

  /**
   * {@inheritdoc}
   *
   * Work with row
   * And wright in $context.
   */
  public function processItem($title, $type, $price, &$context) { 
    
    # Insert in DB row data
      $query = \Drupal::database()->insert('test_module');
      $query->fields([
        'title' => $title,
        'type' => $type,
        'price' => $price,
      ]);
      $query->execute();
      $context['results'][] = $title;
      $context['message'] = 'Item title: "' .$title . '" processing';
  }

  /**
   * {@inheritdoc}
   */
  public function setBatch() {
    batch_set($this->batch);
  }

  /**
   * {@inheritdoc}
   *
   * To call function from module, not from form
   */
  public function processBatch() {
    batch_process();
  }

  /**
   * {@inheritdoc}
   *
   * Information about end of import
   */
  public function finished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()
        ->formatPlural(count($results), 'One post processed.', '@count items processed.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
