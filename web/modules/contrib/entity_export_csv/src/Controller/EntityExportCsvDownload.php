<?php

namespace Drupal\entity_export_csv\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Define the entity export csv download controller.
 */
class EntityExportCsvDownload implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The csrf token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The entity export csv download constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The csrf token generator.
   */
  public function __construct(RequestStack $request_stack, CsrfTokenGenerator $csrf_token) {
    $this->request = $request_stack->getCurrentRequest();
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('csrf_token')
    );
  }

  /**
   * Build the results page.
   *
   * @return array
   *   A Drupal render array.
   */
  public function resultsExport() {
    $request = $this->getRequest();

    if (!$request->query->has('results')) {
      throw new NotFoundHttpException(
        $this->t('Missing export download results.')
      );
    }
    $results = $request->query->get('results');
    $file_uri = isset($results['file']) ? $results['file'] : NULL;
    $token = $this->csrfToken->get($file_uri);
    $query_options = [
      'query' => [
        'token' => $token,
        'file' => $file_uri,
      ],
    ];
    $download_url = Url::fromRoute(
      'entity_content_export.download', [], $query_options
    )->toString();

    $build['results'] = [
      '#markup' => $this->t(
        "The download should automatically start shortly. If it doesn't, click
         <a data-auto-download href='@download_url'>Download</a>.", [
           '@download_url' => $download_url,
         ]
      ),
      '#attached' => [
        'library' => [
          'entity_content_csv/auto-download',
        ],
      ],
    ];

    $build['actions'] = [
      '#type' => 'actions',
    ];
    $build['actions']['back'] = [
      '#type' => 'link',
      '#title' => $this->t('Back to Export page.'),
      '#url' => Url::fromRoute('entity_export_csv.export_form'),
      '#options' => [
        'attributes' => [
          'class' => [
            'btn',
            'btn-primary',
            'btn-export-back',
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Download entity content exported file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function downloadExport() {
    $token = $this->getRequest()->query->get('token');
    $file_uri = $this->getRequest()->query->get('file');
    if (empty($token) || !$this->csrfToken->validate($token, $file_uri)) {
      throw new AccessDeniedHttpException();
    }
    if (!isset($file_uri) || !file_exists($file_uri)) {
      throw new NotFoundHttpException(
        $this->t('Missing or not found entity content exported file.')
      );
    }
    $headers = array (
      'Content-Type'  => 'text/csv',
    );
    return (new BinaryFileResponse($file_uri, 200, $headers))
      ->deleteFileAfterSend(TRUE)
      ->setContentDisposition('attachment', basename($file_uri));
  }

  /**
   * Get current request object.
   *
   * @return null|\Symfony\Component\HttpFoundation\Request
   *   The request.
   */
  protected function getRequest() {
    return $this->request;
  }

}
