<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\dhcr_backend\Utility\DhcrMapConfig;
use Drupal\dhcr_backend\Utility\DhcrCourseStatusConfig;

final class DhcrCourseListBuilder extends EntityListBuilder {
  use DhcrSortableRowsTrait;

  public function load(): array {
    $cutoff = DhcrMapConfig::getCourseArchiveDateCutoff();
    $storage = $this->getStorage();
    $sort = $this->getRequestedSort(['updated', 'published', 'title'], 'title');
    $direction = $this->getRequestedDirection();
    $sort_field = match ($sort) {
      'updated' => 'changed',
      'published' => 'active',
      default => 'title',
    };

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('archived', 0)
      ->condition('changed', $cutoff, '>')
      ->sort($sort_field, $direction);

    if ($sort_field !== 'title') {
      $query->sort('title', 'ASC');
    }

    $ids = $query->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  public function render(): array {
    return $this->buildRenderableFromEntities($this->load(), [
      'heading' => (string) $this->t('All Courses'),
      'icon' => 'list',
      'empty' => (string) $this->t('No courses available.'),
      'show_legend' => TRUE,
      'skip_sort' => TRUE,
    ]);
  }

  public function buildRenderableFromEntities(array $entities, array $options = []): array {
    $rows = [];
    foreach ($entities as $entity) {
      $rows[] = $this->buildRow($entity);
    }

    if (empty($options['skip_sort'])) {
      $rows = $this->sortRows($rows, [
        'updated' => 'updated_sort',
        'published' => 'published_sort',
        'title' => 'course_name',
      ], 'title');
    }

    if (!empty($options['force_updated_class'])) {
      foreach ($rows as &$row) {
        $row['updated_class'] = (string) $options['force_updated_class'];
        if (!empty($options['force_updated_date'])) {
          $timestamp = (int) ($row['updated_timestamp'] ?? 0);
          $row['updated_text'] = $timestamp > 0 ? 'on ' . date('Y-m-d', $timestamp) : 'on -';
        }
      }
      unset($row);
    }

    return [
      '#theme' => 'dhcr_all_courses_list',
      '#heading' => $options['heading'] ?? (string) $this->t('All Courses'),
      '#icon' => $options['icon'] ?? 'list',
      '#show_legend' => $options['show_legend'] ?? TRUE,
      '#sort_links' => $this->buildCourseSortLinks(),
      '#rows' => $rows,
      '#empty' => $options['empty'] ?? (string) $this->t('No courses available.'),
      '#attached' => [
        'library' => ['dhcr_backend/admin_all_courses'],
      ],
      '#cache' => [
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
  }

  public function buildRow(EntityInterface $entity): array {
    $updated_ts = (int) ($entity->get('changed')->value ?? 0);
    $active = (bool) ($entity->get('active')->value ?? FALSE);
    $archived = (bool) ($entity->get('archived')->value ?? FALSE);

    $updated_class = $this->getCourseStatusClass($updated_ts, $active, $archived);
    $updated_text = in_array($updated_class, ['orange', 'red'], TRUE)
      ? ($updated_ts > 0 ? ('on ' . date('Y-m-d', $updated_ts)) : 'on -')
      : $this->formatAgeText($updated_ts);

    $course_type = $entity->get('course_type')->entity;
    $institution = $entity->get('institution')->entity;
    $owner = $entity->get('uid')->entity;

    $course_url = $entity->get('course_url')->first();
    $course_url_uri = $course_url ? (string) ($course_url->get('uri')->value ?? '') : '';

    $actions = [
      [
        'label' => (string) $this->t('Update'),
        'url' => $entity->toUrl('edit-form')->toString(),
      ],
      [
        'label' => (string) $this->t('Ext. Resources'),
        'url' => Url::fromRoute('entity.dhcr_external_resource.collection', [], ['query' => ['course' => $entity->id()]])->toString(),
      ],
      [
        'label' => (string) $this->t('Share'),
        'url' => $entity->toUrl('edit-form', ['query' => ['action' => 'share']])->toString(),
      ],
      [
        'label' => (string) $this->t('Transfer'),
        'url' => $entity->toUrl('edit-form', ['query' => ['action' => 'transfer']])->toString(),
      ],
    ];

    return [
      'actions' => $actions,
      'updated_class' => $updated_class,
      'updated_text' => $updated_text,
      'updated_sort' => $updated_ts,
      'updated_timestamp' => $updated_ts,
      'published_text' => $active ? 'Yes' : 'No',
      'published_class' => $active ? 'yes' : 'no',
      'published_sort' => $active ? 1 : 0,
      'course_name' => (string) $entity->label(),
      'course_edit_url' => $entity->toUrl('edit-form')->toString(),
      'education_type' => $course_type ? (string) $course_type->label() : '',
      'institution' => $institution ? (string) $institution->label() : '',
      'owner' => $owner ? (string) $owner->label() : '',
      'course_url' => $course_url_uri,
      'course_url_label' => $course_url_uri !== '' ? (string) $this->t('Link') : '',
    ];
  }

  private function formatAgeText(int $timestamp): string {
    if ($timestamp <= 0) {
      return '-';
    }

    $interval = \Drupal::service('date.formatter')->formatTimeDiffSince($timestamp);
    return $interval . ' ago';
  }

  public function getCourseStatusClass(int $updated_ts, bool $active, bool $archived): string {
    if (!$active || $archived) {
      return 'red';
    }

    $yellow_threshold = DhcrCourseStatusConfig::yellowDate();
    $red_threshold = DhcrCourseStatusConfig::redDate();
    $archive_threshold = DhcrCourseStatusConfig::archiveDate();

    if ($updated_ts > 0 && $updated_ts < $archive_threshold) {
      return 'red';
    }

    if ($updated_ts > 0 && $updated_ts < $red_threshold) {
      return 'red';
    }

    if ($updated_ts > 0 && $updated_ts < $yellow_threshold) {
      return 'orange';
    }

    return 'green';
  }

  private function buildCourseSortLinks(): array {
    return $this->buildSortLinksWithDefault('title');
  }

  private function buildSortLinksWithDefault(string $default_sort): array {
    return $this->buildSortLinks([
      'updated' => (string) $this->t('Updated'),
      'published' => (string) $this->t('Published'),
      'title' => (string) $this->t('Course Name'),
    ], $default_sort);
  }

}
