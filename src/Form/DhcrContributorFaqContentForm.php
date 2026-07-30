<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\dhcr_backend\Controller\DhcrFaqQuestionController;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class DhcrContributorFaqContentForm extends FormBase {

  public function __construct(
    private readonly StateInterface $state,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('state'),
    );
  }

  public function getFormId(): string {
    return 'dhcr_contributor_faq_content_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attributes']['class'][] = 'dhcr-contributor-faq-content-form';
    $form['#attached']['library'][] = 'dhcr_backend/admin_faq_question';

    $form['heading'] = [
      '#type' => 'markup',
      '#markup' => '<h2 class="dhcr-faq-questions-list__heading">' . $this->t('Edit Contributor FAQ') . '</h2>',
    ];

    $format = $this->storedTextFormat();
    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Contributor FAQ content'),
      '#default_value' => (string) $this->state->get(
        DhcrFaqQuestionController::CONTRIBUTOR_FAQ_CONTENT_STATE_KEY,
        DhcrFaqQuestionController::CONTRIBUTOR_FAQ_DEFAULT_CONTENT
      ),
      '#format' => $format,
      '#allowed_formats' => [$format],
      '#rows' => 28,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Contributor FAQ'),
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

    $this->state->set(DhcrFaqQuestionController::CONTRIBUTOR_FAQ_CONTENT_STATE_KEY, (string) ($content['value'] ?? ''));
    $this->state->set(DhcrFaqQuestionController::CONTRIBUTOR_FAQ_FORMAT_STATE_KEY, (string) ($content['format'] ?? $this->storedTextFormat()));

    $this->messenger()->addStatus($this->t('Contributor FAQ content has been saved.'));
    $form_state->setRedirect('dhcr_backend.help_contributor_faq');
  }

  private function storedTextFormat(): string {
    $stored_format = (string) $this->state->get(
      DhcrFaqQuestionController::CONTRIBUTOR_FAQ_FORMAT_STATE_KEY,
      DhcrFaqQuestionController::CONTRIBUTOR_FAQ_DEFAULT_FORMAT
    );

    if ($this->formatExists($stored_format)) {
      return $stored_format;
    }

    foreach (['full_html', 'basic_html', 'plain_text'] as $format) {
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
