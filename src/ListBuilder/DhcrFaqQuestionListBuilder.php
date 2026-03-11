<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

final class DhcrFaqQuestionListBuilder extends EntityListBuilder {
  use DhcrSortableRowsTrait;

  public function render(): array {
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
      '#add_url' => Url::fromRoute('entity.dhcr_faq_question.add_form')->toString(),
      '#sort_links' => $this->buildSortLinks([
        'id' => (string) $this->t('Id'),
        'category' => (string) $this->t('Category'),
        'sort_order' => (string) $this->t('Sort order'),
        'question' => (string) $this->t('Question'),
        'published' => (string) $this->t('Published'),
      ], 'category'),
      '#rows' => $rows,
      '#empty' => $this->t('No FAQ questions available.'),
      '#attached' => [
        'library' => ['dhcr_backend/admin_faq_question'],
      ],
      '#cache' => [
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
  }

  protected function getEntityIds(): array {
    return array_values(
      $this->getStorage()
        ->getQuery()
        ->accessCheck(FALSE)
        ->sort('category', 'ASC')
        ->sort('sort_order', 'ASC')
        ->sort('id', 'ASC')
        ->execute()
    );
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
