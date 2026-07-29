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
      'created' => 'created_sort',
      'changed' => 'changed_sort',
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
        'visible' => (string) $this->t('Published'),
        'title' => (string) $this->t('Label'),
        'course' => (string) $this->t('Course name'),
        'type' => (string) $this->t('Type'),
        'created' => (string) $this->t('Created'),
        'changed' => (string) $this->t('Modified'),
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
    $visible = (int) ($entity->get('visible')->value ?? 0) === 1;
    $created = (int) ($entity->get('created')->value ?? 0);
    $changed = (int) ($entity->get('changed')->value ?? 0);

    return [
      'id' => (string) $entity->id(),
      'title' => (string) $entity->label(),
      'course' => $course ? (string) $course->label() : '',
      'course_url' => $course ? Url::fromRoute('entity.dhcr_external_resource.collection', [], ['query' => ['course' => $course->id()]])->toString() : '',
      'type' => (string) ($entity->get('resource_type')->value ?? ''),
      'visible' => $visible ? (string) $this->t('Public visible') : (string) $this->t('Not public visible'),
      'visible_class' => $visible ? 'is-visible' : 'is-hidden',
      'visible_sort' => $visible ? 1 : 0,
      'created' => $this->formatDateTime($created),
      'created_sort' => $created,
      'changed' => $this->formatDateTime($changed),
      'changed_sort' => $changed,
      'resource_url' => $resource_url,
      'edit_url' => $entity->toUrl('edit-form')->toString(),
    ];
  }

  private function formatDateTime(int $timestamp): string {
    if ($timestamp <= 0) {
      return '';
    }

    return \Drupal::service('date.formatter')->format($timestamp, 'custom', 'n/j/y, g:i A');
  }
}
