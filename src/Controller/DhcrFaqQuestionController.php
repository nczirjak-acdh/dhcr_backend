<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\dhcr_backend\Entity\FaqQuestion;
use Drupal\dhcr_backend\Form\DhcrFaqQuestionOrderForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DhcrFaqQuestionController extends ControllerBase {

  private const CATEGORIES = ['public', 'contributor', 'moderator'];

  public function categoryList(string $category): array {
    if (!in_array($category, self::CATEGORIES, TRUE)) {
      throw new NotFoundHttpException();
    }

    return $this->formBuilder()->getForm(DhcrFaqQuestionOrderForm::class, $category, $this->categoryTitle($category));
  }

  public function view(FaqQuestion $dhcr_faq_question): array {
    $category = (string) ($dhcr_faq_question->get('category')->value ?? '');
    $allowed_values = $dhcr_faq_question->getFieldDefinition('category')->getSetting('allowed_values') ?? [];
    $answer = $dhcr_faq_question->get('answer')->first();
    $link = $dhcr_faq_question->get('link_url')->first();
    $link_url = (string) ($link?->uri ?? '');
    $link_title = (string) ($dhcr_faq_question->get('link_title')->value ?: $link?->title ?: $link_url);
    $published = (int) ($dhcr_faq_question->get('published')->value ?? 0) === 1;

    return [
      '#theme' => 'dhcr_faq_question_details',
      '#title' => (string) $dhcr_faq_question->label(),
      '#edit_url' => $dhcr_faq_question->toUrl('edit-form')->toString(),
      '#category' => (string) ($allowed_values[$category] ?? $category),
      '#published' => $published,
      '#answer' => [
        '#type' => 'processed_text',
        '#text' => (string) ($answer?->value ?? ''),
        '#format' => (string) ($answer?->format ?? 'plain_text'),
      ],
      '#link_title' => $link_title,
      '#link_url' => $link_url,
      '#attached' => [
        'library' => ['dhcr_backend/admin_faq_question'],
      ],
      '#cache' => [
        'tags' => $dhcr_faq_question->getCacheTags(),
      ],
    ];
  }

  private function categoryTitle(string $category): string {
    return match ($category) {
      'public' => (string) $this->t('Public Questions'),
      'contributor' => (string) $this->t('Contributor Questions'),
      'moderator' => (string) $this->t('Moderator Questions'),
      default => '',
    };
  }

}
