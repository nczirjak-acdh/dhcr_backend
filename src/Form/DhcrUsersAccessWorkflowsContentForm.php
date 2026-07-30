<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\dhcr_backend\Controller\DhcrHelpContentController;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class DhcrUsersAccessWorkflowsContentForm extends FormBase {

  public function __construct(
    private readonly StateInterface $state,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('state'),
    );
  }

  public function getFormId(): string {
    return 'dhcr_users_access_workflows_content_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attributes']['class'][] = 'dhcr-help-content-form';
    $form['#attached']['library'][] = 'dhcr_backend/admin_help';

    $form['heading'] = [
      '#type' => 'markup',
      '#markup' => '<h2 class="dhcr-faq-content__heading">' . $this->t('Edit Users, Access and Workflows') . '</h2>',
    ];

    $format = $this->storedTextFormat();
    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Users, Access and Workflows content'),
      '#default_value' => (string) $this->state->get(
        DhcrHelpContentController::USERS_ACCESS_WORKFLOWS_CONTENT_STATE_KEY,
        DhcrHelpContentController::USERS_ACCESS_WORKFLOWS_DEFAULT_CONTENT
      ),
      '#format' => $format,
      '#allowed_formats' => [$format],
      '#rows' => 32,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save content'),
      '#attributes' => [
        'class' => ['button--dhcr-outline'],
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $content = $form_state->getValue('content');
    if (!is_array($content)) {
      return;
    }

    $this->state->set(DhcrHelpContentController::USERS_ACCESS_WORKFLOWS_CONTENT_STATE_KEY, (string) ($content['value'] ?? ''));
    $this->state->set(DhcrHelpContentController::USERS_ACCESS_WORKFLOWS_FORMAT_STATE_KEY, (string) ($content['format'] ?? $this->storedTextFormat()));

    $this->messenger()->addStatus($this->t('Users, Access and Workflows content has been saved.'));
    $form_state->setRedirect('dhcr_backend.help_users_access_workflows');
  }

  private function storedTextFormat(): string {
    $stored_format = (string) $this->state->get(
      DhcrHelpContentController::USERS_ACCESS_WORKFLOWS_FORMAT_STATE_KEY,
      DhcrHelpContentController::USERS_ACCESS_WORKFLOWS_DEFAULT_FORMAT
    );

    if ($this->formatExists($stored_format)) {
      return $stored_format;
    }

    foreach (['full_html', 'basic_html', 'filtered_html', 'plain_text'] as $format) {
      if ($this->formatExists($format)) {
        return $format;
      }
    }

    return 'plain_text';
  }

  private function formatExists(string $format): bool {
    return FilterFormat::load($format) !== NULL;
  }

}
