<?php

namespace Drupal\entity_export_csv\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entity_export_csv\EntityExportCsvManagerInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityExportCsvForm.
 */
class EntityExportCsvForm extends EntityForm {

  use EntityExportCsvTrait;

  /**
   * The FieldTypeExportManager.
   *
   * @var \Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface
   */
  protected $fieldTypeExportManager;

  /**
   * Drupal\user\UserDataInterface definition.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The entity export csv manager.
   *
   * @var \Drupal\entity_export_csv\EntityExportCsvManagerInterface
   */
  protected $manager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Define entity export csv form constructor.
   *
   * @param \Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface $field_type_export_manager
   *   The field type export manager.
   * @param \Drupal\entity_export_csv\EntityExportCsvManagerInterface $entity_export_csv_manager
   *   The entity export csv manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(FieldTypeExportManagerInterface $field_type_export_manager, EntityExportCsvManagerInterface $entity_export_csv_manager, LanguageManagerInterface $language_manager) {
    $this->fieldTypeExportManager = $field_type_export_manager;
    $this->manager = $entity_export_csv_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field_type_export'),
      $container->get('entity_export_csv.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\entity_export_csv\Entity\EntityExportCsvInterface $entity_export_csv */
    $entity_export_csv = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_export_csv->label(),
      '#description' => $this->t("Label for the Entity export csv."),
      '#required' => TRUE,
      '#weight' => -100,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_export_csv->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_export_csv\Entity\EntityExportCsv::load',
      ],
      '#disabled' => !$entity_export_csv->isNew(),
      '#weight' => -99,
    ];

    $form['#attached']['library'][] = 'entity_export_csv/export_form';

    $options = $this->manager->getContentEntityTypesEnabled(TRUE);
    if (empty($options)) {
      $this->messenger()->addWarning(
        $this->t('No entity type have been configured to be exported.')
      );
      return $form;
    }

    if ($this->languageManager->isMultilingual()) {
      $languages = $this->languageManager->getLanguages();
      $languages_options = [];
      foreach ($languages as $language_id => $language) {
        $languages_options[$language->getId()] = $language->getName();
      }
      $form['langcode'] = [
        '#type' => 'select',
        '#title' => $this->t('Language'),
        '#description' => $this->t('Select the language you want export'),
        '#options' => $languages_options,
        '#default_value' => $entity_export_csv->getLangCode() ?: $this->languageManager->getDefaultLanguage()->getId(),
      ];
    }

    $entity_type_id_default = $entity_export_csv->getTargetEntityTypeId() ?: '';
    $form['entity_type_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#required' => TRUE,
      '#options' => ['' => $this->t('Select')] + $options,
      '#default_value' => $entity_type_id_default,
      '#ajax' => [
        'event' => 'change',
        'method' => 'replaceWith',
        'wrapper' => 'bundle-wrapper',
        'callback' => [$this, 'ajaxReplaceBundleCallback'],
      ],
    ];

    $form['bundle_wrapper'] = [
      '#type' => 'container',
      '#prefix' => '<div id="bundle-wrapper">',
      '#suffix' => '</div>',
    ];

    $entity_type_id = $this->getElementPropertyValue('entity_type_id', $form_state, $entity_type_id_default);
    $bundles = $this->manager->getBundlesEnabledPerEntityType($entity_type_id, TRUE);
    $bundle_default = $entity_export_csv->getTargetBundle() ?: '';
    if ($entity_type_id) {
      $form['bundle_wrapper']['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#description' => $this->t('Select the bundle to export.'),
        '#options' => ['' => $this->t('- Select -')] + $bundles,
        '#default_value' => $this->getElementPropertyValue('bundle', $form_state, $bundle_default),
        '#required' => TRUE,
        '#ajax' => [
          'event' => 'change',
          'method' => 'replaceWith',
          'wrapper' => 'fields-wrapper',
          'callback' => [$this, 'ajaxReplaceFieldsCallback'],
        ],
      ];

      $delimiters = $this->manager->getDelimiters();
      $form['bundle_wrapper']['delimiter'] = [
        '#type' => 'select',
        '#title' => $this->t('Delimiter'),
        '#options' => $delimiters,
        '#default_value' => $entity_export_csv->getDelimiter() ?: ',',
      ];

      $form['bundle_wrapper']['fields'] = [
        '#type' => 'container',
        '#prefix' => '<div id="fields-wrapper">',
        '#suffix' => '</div>',
        '#tree' => TRUE,
        '#attributes' => [
          'class' => [
            'inline-elements',
          ],
        ],
      ];

      $bundle = $this->getElementPropertyValue('bundle', $form_state, $bundle_default);
      if ($bundle) {
        $form['bundle_wrapper']['fields']['#title'] = $this->t('Select fields to export');
        $fields = $this->manager->getBundleFieldsEnabled($entity_type_id, $bundle, TRUE);
        $fields_default_values = $entity_export_csv->getFields() ?: [];
        if (!empty($fields_default_values)) {
          $this->manager->sortNaturalFields($fields, $fields_default_values);
        }
        foreach ($fields as $field_name => $definition) {
          $field_name_class = Html::cleanCssIdentifier($field_name);

          $form['bundle_wrapper']['fields'][$field_name] = [
            '#type' => 'fieldset',
            '#title' => $definition->getLabel(),
            '#tree' => TRUE,
          ];

          $enable_default = isset($fields_default_values[$field_name]['enable']) ? $fields_default_values[$field_name]['enable'] : TRUE;
          $form['bundle_wrapper']['fields'][$field_name]['enable'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable'),
            '#default_value' => $this->getElementPropertyValue(['fields', $field_name, 'enable'], $form_state, $enable_default),
          ];
          $order_default = isset($fields_default_values[$field_name]['order']) ? $fields_default_values[$field_name]['order'] : 0;
          $form['bundle_wrapper']['fields'][$field_name]['order'] = [
            '#type' => 'number',
            '#title' => $this->t('Order'),
            '#required' => TRUE,
            '#default_value' => $this->getElementPropertyValue(['fields', $field_name, 'order'], $form_state, $order_default),
          ];

          $field_type = $definition->getType();
          $exporters = $this->fieldTypeExportManager->getFieldTypeOptions($field_type, $entity_type_id, $bundle, $field_name);
          $exporter_ids = array_keys($exporters);
          $default_exporter = (isset($fields_default_values[$field_name]['exporter']) && isset($exporters[$fields_default_values[$field_name]['exporter']])) ? $fields_default_values[$field_name]['exporter'] : $this->getDefaultExporterId($exporter_ids);
          $default_exporter_value = $this->getElementPropertyValue(['fields', $field_name, 'exporter'], $form_state, $default_exporter);
          $form['bundle_wrapper']['fields'][$field_name]['exporter'] = [
            '#type' => 'select',
            '#title' => $this->t('Export format'),
            '#options' => $exporters,
            '#default_value' => $default_exporter_value,
            '#required' => TRUE,
            '#ajax' => [
              'event' => 'change',
              'method' => 'replaceWith',
              'wrapper' => 'fields-wrapper',
              'callback' => [$this, 'ajaxReplaceFieldsCallback'],
            ],
          ];

          $form['bundle_wrapper']['fields'][$field_name]['form'] = [
            '#type' => 'container',
            '#prefix' => '<div id="export-form-wrapper-"' . $field_name_class . '>',
            '#suffix' => '</div>',
          ];
          $triggering = $form_state->getTriggeringElement();
          if (!empty($triggering['#name']) && $triggering['#name'] === 'fields[' . $field_name . '][exporter]') {
              $default_exporter_value = $triggering['#value'];
          }
          // @TODO handle configuration values
          $configuration_default = isset($fields_default_values[$field_name]['form']['options']) ? $fields_default_values[$field_name]['form']['options'] : [];
          $configuration = $this->getElementPropertyValue(['fields', $field_name, 'form', 'options'], $form_state, $configuration_default);
          /** @var \Drupal\entity_export_csv\Plugin\FieldTypeExportInterface $plugin */
          $plugin = $this->fieldTypeExportManager->createInstance($default_exporter_value, $configuration);
          $form['bundle_wrapper']['fields'][$field_name]['form']['options'] = $plugin->buildConfigurationForm([], $form_state, $definition);
        }
      }
    }

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity_export_csv->status(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_export_csv = $this->entity;
    $status = $entity_export_csv->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Entity export csv.', [
          '%label' => $entity_export_csv->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Entity export csv.', [
          '%label' => $entity_export_csv->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity_export_csv->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    if (empty($values['entity_type_id'])) {
      $form_state->setError($form['entity_type_id'], $this->t('The entity type ID is mandatory. Please select one and configure the export csv configuration.'));
    }
    parent::validateForm($form, $form_state);
  }

}
