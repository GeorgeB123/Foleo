<?php

namespace Drupal\ft_school\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ft_school\Services\SchoolImporter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SchoolImportForm extends FormBase {

  /**
   * The school importer service.
   *
   * @var \Drupal\ft_school\Services\SchoolImporter
   */
  protected $schoolImporter;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  // @todo: save endpoint to import config.
  const CONFIG = 'school_import_config';

  /**
   * Constructs a new SchoolImportForm.
   *
   * @param \Drupal\ft_school\Services\SchoolImporter $school_importer
   *   The school importer service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(SchoolImporter $school_mporter, ConfigFactoryInterface $config_factory) {
    $this->schoolImporter = $school_mporter;
    $this->configFactory = $config_factory->getEditable(self::CONFIG);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('school_importer'),
      $container->get('config.factory')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'school_import_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = ['absolute' => TRUE, 'attributes' => ['target' => '_blank']];
    $url = Url::fromUri('https://catalogue.data.govt.nz/dataset/directory-of-educational-institutions/resource/20b7c271-fd5a-4c9e-869b-481a0e2453cd', $options);
    $form['endpoint'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Latest School List'),
      '#required' => TRUE,
      '#default_value' => 'https://catalogue.data.govt.nz/dataset/c1923d33-e781-46c9-9ea1-d9b850082be4/resource/20b7c271-fd5a-4c9e-869b-481a0e2453cd/download/schooldirectory-01-11-2020-083005.csv',
      '#description' => $this->t('The latest csv can be found on the ' . Link::fromTextAndUrl('Government Website', $url)->toString()),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $csv = $form_state->getValue('endpoint');
    $headers = $this->schoolImporter->getCsvHeaders($csv);
    $rows = $this->schoolImporter->fetchRows($csv);
    $operations = [];
    foreach (array_chunk($rows, 100) as $row_chunk) {
      $operations[] = [
        'ft_school_batch_process', [
          $headers,
          $row_chunk,
        ],
      ];
    }
    $batch = [
      'title' => t('School Imports Batch'),
      'operations' => $operations,
      'finished' => 'ft_schools_batch_complete',
      'file' => drupal_get_path('module', 'ft_school') . '/ft_school.batch.inc',
    ];
    batch_set($batch);
  }
}
