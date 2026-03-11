<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

final class DhcrCountryListBuilder extends EntityListBuilder {
  use DhcrSortableRowsTrait;

  public function render(): array {
    $rows = [];
    foreach ($this->load() as $entity) {
      $rows[] = $this->buildRow($entity);
    }
    $rows = $this->sortRows($rows, [
      'id' => 'id',
      'name' => 'name',
    ], 'id');

    return [
      '#theme' => 'dhcr_countries_list',
      '#add_url' => Url::fromRoute('entity.dhcr_country.add_form')->toString(),
      '#sort_links' => $this->buildSortLinks([
        'id' => (string) $this->t('Id'),
        'name' => (string) $this->t('Name'),
      ], 'id'),
      '#rows' => $rows,
      '#empty' => $this->t('No countries available.'),
      '#attached' => [
        'library' => ['dhcr_backend/admin_country'],
      ],
      '#cache' => [
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
  }

  public function buildRow(EntityInterface $entity): array {
    return [
      'id' => (string) $entity->id(),
      'name' => (string) $entity->label(),
      'edit_url' => $entity->toUrl('edit-form')->toString(),
    ];
  }

}
