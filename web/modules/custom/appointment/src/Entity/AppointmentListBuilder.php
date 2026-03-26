<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Defines the list builder for Appointment entities.
 */
class AppointmentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    $build['#attached']['library'][] = 'appointment/appointment.theme';

    // ==================== FILTER FORM ====================
    $build['filters_form'] = \Drupal::formBuilder()->getForm('Drupal\appointment\Form\AppointmentFilterForm');

    // Get filtered appointments
    $appointments = $this->getFilteredAppointments();

    // ==================== PAGINATION ====================
    $items_per_page = 5;
    $current_page = \Drupal::request()->query->get('page', 0);
    $total_items = count($appointments);
    $total_pages = ceil($total_items / $items_per_page);

    // Get current page appointments
    $start = $current_page * $items_per_page;
    $end = $start + $items_per_page;
    $page_appointments = array_slice($appointments, $start, $items_per_page, TRUE);

    // Build the table
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => [],
      '#empty' => $this->t('No appointments found.'),
      '#attributes' => ['class' => ['appointment-table']],
    ];

    foreach ($page_appointments as $entity) {
      $build['table']['#rows'][] = $this->buildRow($entity);
    }

    // ==================== ADD PAGER ====================
    // Build pagination links
    $build['pager'] = $this->buildPager($current_page, $total_pages, $total_items, $items_per_page);

    return $build;
  }

  /**
   * Build pager for pagination.
   */
  protected function buildPager($current_page, $total_pages, $total_items, $items_per_page) {
    $pager = [
      '#type' => 'container',
      '#attributes' => ['class' => ['appointment-pager']],
    ];

    // Previous button
    if ($current_page > 0) {
      $query_params = \Drupal::request()->query->all();
      $query_params['page'] = $current_page - 1;
      $prev_url = Url::fromRoute('entity.appointment.collection', [], ['query' => $query_params]);
      $pager['prev'] = [
        '#type' => 'link',
        '#title' => $this->t('← Previous'),
        '#url' => $prev_url,
        '#attributes' => ['class' => ['pager-link', 'pager-prev']],
      ];
    }

    // Page info
    $start_item = ($current_page * $items_per_page) + 1;
    $end_item = min(($current_page + 1) * $items_per_page, $total_items);
    $pager['info'] = [
      '#markup' => $this->t('Showing @start to @end of @total items (Page @current of @total_pages)',
        [
          '@start' => $start_item,
          '@end' => $end_item,
          '@total' => $total_items,
          '@current' => $current_page + 1,
          '@total_pages' => $total_pages,
        ]
      ),
    ];

    // Next button
    if ($current_page < $total_pages - 1) {
      $query_params = \Drupal::request()->query->all();
      $query_params['page'] = $current_page + 1;
      $next_url = Url::fromRoute('entity.appointment.collection', [], ['query' => $query_params]);
      $pager['next'] = [
        '#type' => 'link',
        '#title' => $this->t('Next →'),
        '#url' => $next_url,
        '#attributes' => ['class' => ['pager-link', 'pager-next']],
      ];
    }

    return $pager;
  }

  /**
   * Get filtered appointments based on query parameters.
   */
  protected function getFilteredAppointments(): array {
    $storage = \Drupal::entityTypeManager()->getStorage('appointment');
    $query = $storage->getQuery()
      ->accessCheck(FALSE);

    $request = \Drupal::request();
    $adviser_id = $request->query->get('adviser');
    $agency_id = $request->query->get('agency');
    $date_from = $request->query->get('date_from');
    $date_to = $request->query->get('date_to');
    $status = $request->query->get('status');


    if (!empty($adviser_id)) {
      $query->condition('adviser', $adviser_id);
    }


    if (!empty($agency_id)) {
      $query->condition('agency', $agency_id);
    }

    // Apply date range filter
    if (!empty($date_from)) {
      // Convert date to string format for comparison (YYYY-MM-DD)
      $from_date_str = $date_from;  // Already in YYYY-MM-DD format
      $query->condition('appointment_date', $from_date_str, '>=');
    }

    if (!empty($date_to)) {
      $to_date = \DateTime::createFromFormat('Y-m-d', $date_to);
      $to_date->modify('+1 day');
      $to_date_str = $to_date->format('Y-m-d');
      $query->condition('appointment_date', $to_date_str, '<');
    }


    if (!empty($status)) {
      $query->condition('status', $status);
    }


    $query->sort('appointment_date', 'DESC');

    $ids = $query->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['customer_name'] = $this->t('Customer');
    $header['appointment_date'] = $this->t('Date/Time');
    $header['status'] = $this->t('Status');
    $header['agency'] = $this->t('Agency');
    $header['adviser'] = $this->t('Adviser');
    $header['operations'] = $this->t('Operations');

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\appointment\Entity\AppointmentEntity $entity */
    $row['id'] = $entity->id();

    // Title as link
    $row['title'] = Link::createFromRoute(
      $entity->getTitle(),
      'entity.appointment.canonical',
      ['appointment' => $entity->id()]
    );

    // Customer name
    $row['customer_name'] = $entity->getCustomerName();

    // Appointment date
    $row['appointment_date'] = $entity->getAppointmentDate();

    // Status
    $row['status'] = $entity->getStatus();

    // Agency name
    $agency_id = $entity->getAgencyId();
    if ($agency_id) {
      $agency = \Drupal::entityTypeManager()->getStorage('agency')->load($agency_id);
      $row['agency'] = $agency ? $agency->getName() : 'N/A';
    } else {
      $row['agency'] = 'N/A';
    }

    // Adviser name
    $adviser_id = $entity->getAdviserId();
    if ($adviser_id) {
      $adviser = \Drupal::entityTypeManager()->getStorage('user')->load($adviser_id);
      $row['adviser'] = $adviser ? $adviser->getDisplayName() : 'N/A';
    } else {
      $row['adviser'] = 'N/A';
    }

    // Operations
    $edit_url = Url::fromRoute('entity.appointment.edit_form', ['appointment' => $entity->id()]);
    $delete_url = Url::fromRoute('entity.appointment.delete_form', ['appointment' => $entity->id()]);

    $row['operations'] = [
      'data' => [
        '#type' => 'dropbutton',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => $edit_url,
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => $delete_url,
          ],
        ],
      ],
    ];

    return $row;
  }

}
