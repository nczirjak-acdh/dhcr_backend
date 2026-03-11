<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

final class DhcrInstitutionListBuilder extends EntityListBuilder {
  use DhcrSortableRowsTrait;

  public function render(): array {
    $rows = [];
    foreach ($this->load() as $entity) {
      $rows[] = $this->buildRow($entity);
    }
    $rows = $this->sortRows($rows, [
      'id' => 'id',
      'name' => 'name',
      'city' => 'city',
      'country' => 'country',
    ], 'id');

    return [
      '#theme' => 'dhcr_institutions_list',
      '#add_url' => Url::fromRoute('entity.dhcr_institution.add_form')->toString(),
      '#sort_links' => $this->buildSortLinks([
        'id' => (string) $this->t('Id'),
        'name' => (string) $this->t('Name'),
        'city' => (string) $this->t('City'),
        'country' => (string) $this->t('Country'),
      ], 'id'),
      '#rows' => $rows,
      '#empty' => $this->t('No institutions available.'),
      '#attached' => [
        'library' => ['dhcr_backend/admin_institution'],
      ],
      '#cache' => [
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
  }

  public function buildRow(EntityInterface $entity): array {
    $city = $entity->get('city')->entity;
    $country = $entity->get('country')->entity;

    return [
      'id' => (string) $entity->id(),
      'name' => (string) $entity->label(),
      'city' => $city ? (string) $city->label() : '',
      'country' => $country ? (string) $country->label() : '',
      'edit_url' => $entity->toUrl('edit-form')->toString(),
    ];
  }

}
