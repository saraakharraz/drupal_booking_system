<?php

namespace Drupal\entity_export_csv\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a trait for Entity Export Csv form.
 */
trait EntityExportCsvTrait {

  /**
   * Get the default exporter id for a field type.
   *
   * @param array $exporter_ids
   *   The exporter ids.
   *
   * @return string
   *   The default exporter id.
   */
  protected function getDefaultExporterId(array $exporter_ids) {
    $default_exporter = reset($exporter_ids);
    return $default_exporter;
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The bundle wrapper form.
   */
  public function ajaxReplaceBundleCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_wrapper'];
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The fields form.
   */
  public function ajaxReplaceFieldsCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_wrapper']['fields'];
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form field configuration.
   */
  public function ajaxReplaceExporterCallback(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parent = $triggering_element['#parents'][0];
    return $form['bundle_wrapper']['fields'][$parent]['form'];
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form.
   */
  public function ajaxReplaceCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Get entity content export settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration instance.
   */
  protected function getConfiguration() {
    return $this->configFactory->get('entity_export_csv.settings');
  }

  /**
   * Get element property value.
   *
   * @param array|string $property
   *   The property.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param mixed $default
   *   The default value.
   *
   * @return array|mixed|null
   *   The property value.
   */
  protected function getElementPropertyValue($property, FormStateInterface $form_state, $default = '', $triggering_element = NULL) {
    if (!empty($triggering_element['#name']) && $triggering_element['#name'] === 'bundle') {
      return $default;
    }
    return $form_state->hasValue($property)
      ? $form_state->getValue($property)
      : $default;
  }

}
