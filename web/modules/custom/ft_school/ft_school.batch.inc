<?php

/**
 * Batch process to create all schools.
 *
 * @param $headers
 * @param $rows
 * @param $context
 */
function ft_school_batch_process($headers, $rows, &$context) {
  $school_importer = \Drupal::service('school_importer');
  foreach ($rows as $row) {
    $school_importer->createSchool($row, $headers);
  }
}

/**
 * Batch success callback.
 *
 * @param $success
 * @param $results
 * @param $operations
 */
function ft_school_batch_complete($success, $results, $operations) {
  if ($success) {
    $message = \Drupal::translation()->formatPlural(count($results), 'One school imported.', 'All Schools Imported.');
  }
  else {
    $message = t('Finished with an error.');
  }

  \Drupal::messenger()->addMessage($message);

}
