<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

final class DhcrExternalResourceListBuilder extends EntityListBuilder {
  use DhcrSortableRowsTrait;

  public function render(): array {
    $course_id = (int) \Drupal::request()->query->get('course');
    $course = $course_id > 0 ? $this->storage->load($course_id) : NULL;

    $ids = $this->getEntityIds();
    $rows = [];
    foreach ($this->storage->loadMultiple($ids) as $entity) {
      $rows[] = $this->buildRow($entity);
    }
    $rows = $this->sortRows($rows, [
      'id' => 'id',
      'title' => 'title',
      'course' => 'course',
      'type' => 'type',
      'visible' => 'visible_sort',
    ], 'id');

    $add_url = Url::fromRoute(
      'entity.dhcr_external_resource.add_form',
      [],
      $course_id > 0 ? ['query' => ['course' => $course_id]] : []
    )->toString();

    return [
      '#theme' => 'dhcr_external_resources_list',
      '#title_suffix' => $course ? (' - ' . $course->label()) : '',
      '#add_url' => $add_url,
      '#sort_links' => $this->buildSortLinks([
        'id' => (string) $this->t('Id'),
        'title' => (string) $this->t('Label'),
        'course' => (string) $this->t('Course'),
        'type' => (string) $this->t('Type'),
        'visible' => (string) $this->t('Visible'),
      ], 'id'),
      '#rows' => $rows,
      '#empty' => $this->t('No external resources available.'),
      '#attached' => [
        'library' => ['dhcr_backend/admin_external_resource'],
      ],
      '#cache' => [
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
  }

  protected function getEntityIds(): array {
    $query = $this->getStorage()->getQuery()->accessCheck(FALSE)->sort('created', 'DESC');
    $course_id = (int) \Drupal::request()->query->get('course');
    if ($course_id > 0) {
      $query->condition('course', $course_id);
    }
    return array_values($query->execute());
  }

  public function buildRow(EntityInterface $entity): array {
    $course = $entity->get('course')->entity;
    $url_item = $entity->get('resource_url')->first();
    $resource_url = $url_item ? (string) ($url_item->get('uri')->value ?? '') : '';

    return [
      'id' => (string) $entity->id(),
      'title' => (string) $entity->label(),
      'course' => $course ? (string) $course->label() : '',
      'type' => (string) ($entity->get('resource_type')->value ?? ''),
      'visible' => ((int) ($entity->get('visible')->value ?? 0) === 1) ? 'Yes' : 'No',
      'visible_sort' => (int) ($entity->get('visible')->value ?? 0),
      'resource_url' => $resource_url,
      'edit_url' => $entity->toUrl('edit-form')->toString(),
    ];
  }
}
