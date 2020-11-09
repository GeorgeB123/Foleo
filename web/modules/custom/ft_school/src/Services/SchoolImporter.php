<?php

namespace Drupal\ft_school\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SchoolImporter
 * @package Drupal\ft_school\Services
 */
class SchoolImporter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SchoolImporter.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Fetch all the rows from the csv.
   *
   * @param $file
   * @return array
   */
  public function fetchRows($file) {
    $file_data = fopen($file,'r');
    $row = 0;
    $rows = [];
    while (($line = fgetcsv($file_data)) !== FALSE) {

      if ($row == 0) {
        $row++;
        continue;
      }
      $rows[] = $line;
      $row++;
    }
    return $rows;
  }

  /**
   * Fetch the headers from the csv file.
   *
   * @param $file
   * @return array
   */
  public function getCsvHeaders($file) {
    $file_data = fopen($file,'r');
    $field_mappings = $this->mapFields();
    $headers = [];
    while (($line = fgetcsv($file_data)) !== FALSE) {
      foreach ($line as $key => $header) {
        if (array_key_exists($header, $field_mappings)) {
          $headers[$header] = $key;
        }
      }
      return $headers;
    }
  }

  /**
   * Create the school group. Check if created first and update accordingly.
   *
   * @param $line
   * @param $headers
   * @param $label
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createSchool($line, $headers) {
    $label = $line[$headers['Org_Name']];
    $field_mappings = $this->mapFields();
    $shool = $this->entityTypeManager->getStorage('group')->loadByProperties([
      'type' => 'school'
    ]);

    $school = $this->getSchool($label, $line['School_Id']);

    // Create new school group if one does npot already exist.
    if (!$school) {
      $school = $this->entityTypeManager->getStorage('group')->create([
        'type' => 'school',
        'label' => $label,
      ]);
      $school->save();
    }

    // Don't reprocess the school name.
    unset($headers['Org_Name']);

    foreach ($headers as $header => $key) {
      $value = $line[$key];
      $field_to_map = $field_mappings[$header];
      if (is_array($field_to_map)) {
        // @todo: Add Address field handling.
      }
      else {
        $school->set('field_' . $field_to_map, $value);
      }
    }
    $school->save();
  }

  /**
   * Check to see if the school exists before creating a new one.
   *
   * @param $label
   * @param $school_id
   * @return bool|\Drupal\Core\Entity\EntityInterface|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getSchool($label, $school_id) {
    $school = $this->entityTypeManager->getStorage('group')->loadByProperties([
      'type' => 'school',
      'label' => $label,
      'field_school_id' => $school_id,
    ]);
    if ($school) {
      return reset($school);
    }
    return FALSE;
  }

  /**
   * Field mappings from csv to group fields.
   *
   * @return array
   */
  private function mapFields() {
    return [
      'Org_Name' => 'label',
      'School_Id' => 'school_id',
      'Telephone' => 'telephone_number',
      'Email' => 'email',
      'Contact1_Name' => 'contact',
      'URL' => 'url',
      'Decile' => 'decile',
      'Add1_Line1' => [
        'address' => TRUE,
        'field' => 'address_line1',
      ],
      'Add1_Suburb' => [
        'address' => TRUE,
        'field' => 'address_line2',
      ],
      'Add1_City' => [
        'address' => TRUE,
        'field' => 'locality',
      ],
      'Add2_Postal_Code' => [
        'address' => TRUE,
        'field' => 'postal_code',
      ],
    ];
  }

}
