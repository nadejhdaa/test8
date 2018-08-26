<?php

namespace Drupal\test_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\test_module\XMLBatchImport;


/**
 * Class UploadForm.
 *
 * @package Drupal\test_module\Form
 */
class UploadForm extends ConfigFormBase {

 

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['test_module.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('test_module.settings');

    $form['file'] = [
      '#title' => $this->t('XML file'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#default_value' => $config->get('fid') ? [$config->get('fid')] : NULL,
      '#upload_validators' => array(
        'file_validate_extensions' => array('xml'),
      ),
      '#required' => TRUE,
    ];

    # Если загружен файл, отображаем дополнительные элементы формы.
    if (!empty($config->get('fid'))) {
      $file = File::load($config->get('fid'));
      if($file) {
        $created = \Drupal::service('date.formatter')
        ->format($file->created->value, 'medium');

        $form['file_information'] = [
          '#markup' => $this->t('This file was uploaded at @created.', ['@created' => $created]),
        ];
      }
      


      # Add button to start import submit handler.
      $form['actions']['start_import'] = [
        '#type' => 'submit',
        '#value' => $this->t('Start import'),
        '#submit' => ['::startImport'],
        '#weight' => 100,
      ];
    }

     

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('test_module.settings');
    # Get file id saved before from config
    $fid_old = $config->get('fid');
    $fid_form = $form_state->getValue('file')[0];
 
    # Check if file is loaded before
    if (empty($fid_old) || $fid_old != $fid_form) {
      // If file is loaded before remove it from permanent files
      if (!empty($fid_old)) {
        $previous_file = File::load($fid_old);
        \Drupal::service('file.usage')
          ->delete($previous_file, 'test_module', 'config_form', $previous_file->id());
      }
      // To load and rename file in our directory get name and directory url from module config settings
      $destination_dir = $config->get('destination'); 
      $new_filename = $config->get('filename');
      
      // Load file and check if directory exists. If dir is exist move file to it. 
      // Else create required dir and load file to it
      $file = File::load($fid_form);  
      if (file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY)) {
        $file = file_move($file, $destination_dir . '/' . $new_filename, $replace = FILE_EXISTS_RENAME);
      } else { 
        drupal_mkdir($destination_dir);
        $file = file_move($file, $destination_dir . '/' . $new_filename, $replace = FILE_EXISTS_RENAME);
      }
      $file->save();

      // Set file is permanent, to prevent delete it after 6 hours
      \Drupal::service('file.usage')
        ->add($file, 'test_module', 'config_form', $file->id());
      # Save necessary info to module configs settings
      $config->set('fid', $fid_form);
      $config->set('creation', time());
      $config->save();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Start import products data from XML file to DB
   */
  public function startImport(array &$form, FormStateInterface $form_state) {
    $config = $this->config('test_module.settings');
    $fid = $config->get('fid');
    $import = new XMLBatchImport($fid);
    $import->setBatch();
  }
}
