<?php

/**
 * @file
 * Entity Export Csv post update file.
 */

/**
* Clears caches post entity update.
*/
function entity_export_csv_post_update_clear_caches(&$sandbox) {
  \Drupal::entityTypeManager()->clearCachedDefinitions();
}