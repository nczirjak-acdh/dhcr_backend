<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

final class DhcrInviteTranslationListBuilder extends EntityListBuilder {
  use DhcrSortableRowsTrait;

  private const SUBJECT_PREFIX = '[DH Course Registry] ';

  public function render(): array {
    $rows = [];
    foreach ($this->load() as $entity) {
      $rows[] = $this->buildRow($entity);
    }

    $rows = $this->sortRows($rows, [
      'sort_order' => 'sort_order',
      'language' => 'language',
      'subject' => 'subject',
      'published' => 'published',
    ], 'sort_order');

    return [
      '#theme' => 'dhcr_translations_list',
      '#add_url' => Url::fromRoute('entity.dhcr_invite_translation.add_form')->toString(),
      '#sort_links' => $this->buildSortLinks([
        'sort_order' => (string) $this->t('Sort Order'),
        'language' => (string) $this->t('Language'),
        'subject' => (string) $this->t('Subject'),
        'published' => (string) $this->t('Published'),
      ], 'sort_order'),
      '#rows' => $rows,
      '#empty' => $this->t('No translations available.'),
      '#attached' => [
        'library' => ['dhcr_backend/admin_translation'],
      ],
      '#cache' => [
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
  }

  public function buildRow(EntityInterface $entity): array {
    $language = $entity->get('language')->entity;
    $subject = trim((string) $entity->get('subject')->value);

    $subject_display = str_starts_with($subject, self::SUBJECT_PREFIX)
      ? $subject
      : self::SUBJECT_PREFIX . $subject;

    return [
      'id' => (string) $entity->id(),
      'sort_order' => (int) $entity->get('sort_order')->value,
      'language' => $language ? (string) $language->label() : '',
      'subject' => $subject,
      'subject_display' => $subject_display,
      'published' => (int) $entity->get('published')->value === 1 ? (string) $this->t('Yes') : (string) $this->t('No'),
      'edit_url' => $entity->toUrl('edit-form')->toString(),
    ];
  }

}
