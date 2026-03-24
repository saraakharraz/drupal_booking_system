<?php

namespace Drupal\entity_export_csv\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entity_export_csv\EntityExportCsvManagerInterface;
use Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity export csv form.
 */
class EntityExportCsv extends FormBase {

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\entity_export_csv\Plugin\FieldTypeExportManagerInterface $field_type_export_manager
   *   The field type export manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\entity_export_csv\EntityExportCsvManagerInterface $entity_export_csv_manager
   *   The entity export csv manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FieldTypeExportManagerInterface $field_type_export_manager, UserDataInterface $user_data, EntityExportCsvManagerInterface $entity_export_csv_manager, LanguageManagerInterface $language_manager) {
    $this->setConfigFactory($config_factory);
    $this->fieldTypeExportManager = $field_type_export_manager;
    $this->userData = $user_data;
    $this->manager = $entity_export_csv_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.field_type_export'),
      $container->get('user.data'),
      $container->get('entity_export_csv.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_export_csv';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'entity_export_csv/export_form';
    $form['#attached']['library'][] = 'entity_export_csv/download';
    $options = $this->manager->getContentEntityTypesEnabled(TRUE);
    if (empty($options)) {
      $this->messenger()->addWarning(
        $this->t('No entity type have been configured to be exported.')
      );
      return [];
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
        '#default_vlue' => $this->languageManager->getDefaultLanguage()->getId(),
      ];
    }

    $user_data = $this->userData->get('entity_export_csv', $this->currentUser()->id(), 'entity_export_csv') ?: [];
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#required' => TRUE,
      '#options' => $options,
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

    $entity_type_id = $this->getElementPropertyValue('entity_type', $form_state);
    $bundles = $this->manager->getBundlesEnabledPerEntityType($entity_type_id, TRUE);
    if ($entity_type_id) {
      $form['bundle_wrapper']['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#description' => $this->t('Select the bundle to export.'),
        '#options' => ['' => $this->t('- Select -')] + $bundles,
        '#default_value' => $this->getElementPropertyValue('bundle', $form_state, ''),
        '#required' => TRUE,
        '#ajax' => [
          'event' => 'change',
          'method' => 'replaceWith',
          'wrapper' => 'fields-wrapper',
          'callback' => [$this, 'ajaxReplaceFieldsCallback'],
        ],
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

      $bundle = $this->getElementPropertyValue('bundle', $form_state, '');
      if ($bundle) {
        $triggering_element = $form_state->getTriggeringElement();
        // We need to reset the fields user input otherwise when switching
        // between bundle with same fields, the first settings are still selected
        // because the user input is already set for this field (but for
        // an another bundle).
        if (!empty($triggering_element['#name']) && $triggering_element['#name'] === 'bundle') {
          $user_input = $form_state->getUserInput();
          $user_input['fields'] = [];
          $form_state->setUserInput($user_input);
        }
        $form['bundle_wrapper']['fields']['#title'] = $this->t('Select fields to export');
        $fields = $this->manager->getBundleFieldsEnabled($entity_type_id, $bundle, TRUE);
        $user_default_values = [];
        if (!empty($user_data[$entity_type_id][$bundle])) {
          $user_default_values = $user_data[$entity_type_id][$bundle];
        }
        if (!empty($user_default_values)) {
          $this->manager->sortNaturalFields($fields, $user_default_values);
        }

        $default_delimiter = !empty($user_default_values['delimiter']) ? $user_default_values['delimiter'] : ',';
        $form['bundle_wrapper']['fields']['delimiter'] = [
          '#type' => 'select',
          '#title' => $this->t('Delimiter'),
          '#default_value' => $this->getElementPropertyValue(['fields', 'delimiter'], $form_state, $default_delimiter, $triggering_element),
          '#options' => $this->manager->getDelimiters(),
          '#wrapper_attributes' => [
            'class' => [
              'reset-flex',
            ],
          ],
          '#states' => [
            'invisible' => [
              ':input[name="entity_type"]' => ['value' => ''],
            ],
          ],
        ];

        foreach ($fields as $field_name => $definition) {
          $field_name_class = Html::cleanCssIdentifier($field_name);

          $form['bundle_wrapper']['fields'][$field_name] = [
            '#type' => 'fieldset',
            '#title' => $definition->getLabel(),
            '#tree' => TRUE,
          ];

          $enable_default = isset($user_default_values[$field_name]['enable']) ? $user_default_values[$field_name]['enable'] : TRUE;
          $form['bundle_wrapper']['fields'][$field_name]['enable'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable'),
            '#default_value' => $this->getElementPropertyValue(['fields', $field_name, 'enable'], $form_state, $enable_default, $triggering_element),
          ];
          $order_default = isset($user_default_values[$field_name]['order']) ? $user_default_values[$field_name]['order'] : 0;
          $form['bundle_wrapper']['fields'][$field_name]['order'] = [
            '#type' => 'number',
            '#title' => $this->t('Order'),
            '#required' => TRUE,
            '#default_value' => $this->getElementPropertyValue(['fields', $field_name, 'order'], $form_state, $order_default, $triggering_element),
          ];

          $field_type = $definition->getType();
          $exporters = $this->fieldTypeExportManager->getFieldTypeOptions($field_type, $entity_type_id, $bundle, $field_name);
          $exporter_ids = array_keys($exporters);
          $default_exporter = (isset($user_default_values[$field_name]['exporter']) && isset($exporters[$user_default_values[$field_name]['exporter']])) ? $user_default_values[$field_name]['exporter'] : $this->getDefaultExporterId($exporter_ids);
          $default_exporter_value = $this->getElementPropertyValue(['fields', $field_name, 'exporter'], $form_state, $default_exporter, $triggering_element);
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
          if ($triggering['#name'] === 'fields[' . $field_name . '][exporter]') {
            $default_exporter_value = $triggering['#value'];
          }
          // @TODO handle configuration values
          $configuration_default = isset($user_default_values[$field_name]['form']['options']) ? $user_default_values[$field_name]['form']['options'] : [];
          $configuration = $this->getElementPropertyValue(['fields', $field_name, 'form', 'options'], $form_state, $configuration_default, $triggering_element);
          /** @var \Drupal\entity_export_csv\Plugin\FieldTypeExportInterface $plugin */
          $plugin = $this->fieldTypeExportManager->createInstance($default_exporter_value, $configuration);
          $form['bundle_wrapper']['fields'][$field_name]['form']['options'] = $plugin->buildConfigurationForm([], $form_state, $definition);
        }

        $save_default = TRUE;
        $form['bundle_wrapper']['fields']['save'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Save this settings for this bundle (leave unchecked will delete any settings saved).'),
          '#default_value' => $this->getElementPropertyValue(['fields', 'save'], $form_state, $save_default, $triggering_element),
          '#wrapper_attributes' => [
            'class' => [
              'reset-flex',
            ],
          ],
          '#states' => [
            'invisible' => [
              ':input[name="entity_type"]' => ['value' => ''],
            ],
          ],
        ];
      }

    }

    $form['actions']['#type'] = 'actions';

    $form['actions']['export'] = [
      '#type' => 'submit',
      '#name' => 'export',
      '#value' => $this->t('Export'),
      '#states' => [
        'invisible' => [
          ':input[name="entity_type"]' => ['value' => ''],
        ],
      ],
    ];

    $form['actions']['save'] = [
      '#type' => 'submit',
      '#name' => 'save',
      '#value' => $this->t('Save settings'),
      '#attributes' => [
        'class' => [
          'btn-secondary',
        ],
      ],
      '#states' => [
        'invisible' => [
          [':input[name="fields[save]"]' => ['checked' => FALSE]],
          'OR',
          [':input[name="entity_type"]' => ['value' => '']],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    if (!isset($values['entity_type']) || !isset($values['bundle'])) {
      return;
    }
    $save = $values['fields']['save'];
    unset($values['fields']['save']);

    $delimiter = $values['fields']['delimiter'];
    unset($values['fields']['delimiter']);

    $entity_type_id = $values['entity_type'];
    $bundle_id = $values['bundle'];
    $fields = $values['fields'];

    $user_data = $this->userData->get('entity_export_csv', $this->currentUser()->id(), 'entity_export_csv') ?: [];
    if (!empty($save)) {
      $user_data[$entity_type_id][$bundle_id] = ['delimiter' => $delimiter] + $fields;
    }
    else {
      if (isset($user_data[$entity_type_id][$bundle_id])) {
        unset($user_data[$entity_type_id][$bundle_id]);
      }
    }
    $this->userData->set('entity_export_csv', $this->currentUser()->id(), 'entity_export_csv', $user_data);
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] === 'save') {
      $this->messenger()->addStatus($this->t('Settings successfully updated.'));
      return;
    }

    $conditions = !empty($fields['conditions']) ? $fields['conditions'] : [];
    unset($fields['conditions']);

    $langcode = isset($values['langcode']) ? $values['langcode'] : NULL;
    $entity_types = $this->manager->getContentEntityTypesEnabled(TRUE);
    $bundles = $this->manager->getBundlesEnabledPerEntityType($entity_type_id, TRUE);
    $batch = [
      'title' => $this->t('Exporting @entity_type of type @bundle', [
        '@bundle' => $bundles[$bundle_id],
        '@entity_type' => $entity_types[$entity_type_id],
      ]),
      'operations' => [
        [
          '\Drupal\entity_export_csv\EntityExportCsvBatch::export',
          [$entity_type_id, $bundle_id, $fields, $langcode, $conditions, $delimiter],
        ],
      ],
      'finished' => '\Drupal\entity_export_csv\EntityExportCsvBatch::finished',
    ];
    batch_set($batch);
  }

  /**
   * Massage the values.
   *
   * @param array $values
   *   The values to massage.
   */
  protected function massageValues(array &$values) {
  }

}
