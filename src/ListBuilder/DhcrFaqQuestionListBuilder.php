<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

final class DhcrFaqQuestionListBuilder extends EntityListBuilder {
  use DhcrSortableRowsTrait;

  private ?string $category = NULL;

  private string $categoryTitle = '';

  public function setCategory(string $category, string $title): self {
    $this->category = $category;
    $this->categoryTitle = $title;
    return $this;
  }

  public function render(): array {
    if ($this->category === NULL) {
      return [
        '#theme' => 'dhcr_faq_questions_index',
        '#cards' => [
          [
            'title' => (string) $this->t('Public Questions'),
            'url' => Url::fromRoute('dhcr_backend.faq_questions_public')->toString(),
          ],
          [
            'title' => (string) $this->t('Contributor Questions'),
            'url' => Url::fromRoute('dhcr_backend.faq_questions_contributor')->toString(),
          ],
          [
            'title' => (string) $this->t('Moderator Questions'),
            'url' => Url::fromRoute('dhcr_backend.faq_questions_moderator')->toString(),
          ],
        ],
        '#attached' => [
          'library' => ['dhcr_backend/admin_faq_question'],
        ],
        '#cache' => [
          'tags' => $this->entityType->getListCacheTags(),
        ],
      ];
    }

    $rows = [];
    foreach ($this->storage->loadMultiple($this->getEntityIds()) as $entity) {
      $rows[] = $this->buildRow($entity);
    }
    $rows = $this->sortRows($rows, [
      'id' => 'id',
      'category' => 'category',
      'sort_order' => 'sort_order',
      'question' => 'question',
      'published' => 'published_sort',
    ], 'category');

    return [
      '#theme' => 'dhcr_faq_questions_list',
      '#title' => $this->categoryTitle,
      '#add_url' => Url::fromRoute('entity.dhcr_faq_question.add_form', [], [
        'query' => ['category' => $this->category],
      ])->toString(),
      '#sort_links' => $this->buildSortLinks([
        'id' => (string) $this->t('Id'),
        'sort_order' => (string) $this->t('Sort order'),
        'question' => (string) $this->t('Question'),
        'published' => (string) $this->t('Published'),
      ], 'sort_order'),
      '#rows' => $rows,
      '#empty' => $this->t('No @category available.', ['@category' => mb_strtolower($this->categoryTitle)]),
      '#attached' => [
        'library' => ['dhcr_backend/admin_faq_question'],
      ],
      '#cache' => [
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
  }

  protected function getEntityIds(): array {
    $query = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('sort_order', 'ASC')
      ->sort('id', 'ASC');

    if ($this->category !== NULL) {
      $query->condition('category', $this->category);
    }

    return array_values($query->execute());
  }

  public function buildRow(EntityInterface $entity): array {
    $allowed_values = $entity->getFieldDefinition('category')->getSetting('allowed_values') ?? [];
    $category = (string) ($entity->get('category')->value ?? '');
    $category_label = $allowed_values[$category] ?? $category;

    return [
      'id' => (string) $entity->id(),
      'category' => (string) $category_label,
      'sort_order' => (string) ($entity->get('sort_order')->value ?? '0'),
      'question' => (string) $entity->label(),
      'published' => ((int) ($entity->get('published')->value ?? 0) === 1) ? 'Yes' : 'No',
      'published_sort' => (int) ($entity->get('published')->value ?? 0),
      'edit_url' => $entity->toUrl('edit-form')->toString(),
    ];
  }
}
