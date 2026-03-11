<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\ListBuilder;

use Drupal\Core\Link;
use Drupal\Core\Url;

trait DhcrSortableRowsTrait {

  protected function sortRows(array $rows, array $sort_map, string $default_sort): array {
    $sort = $this->getRequestedSort(array_keys($sort_map), $default_sort);
    $direction = $this->getRequestedDirection();
    $field = $sort_map[$sort];

    usort($rows, static function (array $a, array $b) use ($field, $direction): int {
      $value_a = $a[$field] ?? '';
      $value_b = $b[$field] ?? '';

      $result = self::compareSortableValues($value_a, $value_b);
      if ($result === 0) {
        $result = self::compareSortableValues($a['id'] ?? 0, $b['id'] ?? 0);
      }

      return $direction === 'ASC' ? $result : -$result;
    });

    return $rows;
  }

  protected function buildSortLinks(array $columns, string $default_sort): array {
    $links = [];
    foreach ($columns as $sort => $label) {
      $links[$sort] = $this->buildSortLink($sort, $label, array_keys($columns), $default_sort);
    }
    return $links;
  }

  private function buildSortLink(string $sort, string $label, array $allowed_sorts, string $default_sort): array {
    $active = $this->getRequestedSort($allowed_sorts, $default_sort) === $sort;
    $direction = $active && $this->getRequestedDirection() === 'ASC' ? 'DESC' : 'ASC';
    $query = \Drupal::request()->query->all();
    $query['sort'] = $sort;
    $query['direction'] = $direction;

    return [
      'text' => Link::fromTextAndUrl($label, Url::fromRoute('<current>', [], ['query' => $query]))->toString(),
      'active' => $active,
      'direction' => $active ? strtolower($this->getRequestedDirection()) : '',
    ];
  }

  private function getRequestedSort(array $allowed_sorts, string $default_sort): string {
    $sort = (string) \Drupal::request()->query->get('sort', $default_sort);
    return in_array($sort, $allowed_sorts, TRUE) ? $sort : $default_sort;
  }

  private function getRequestedDirection(): string {
    $direction = strtoupper((string) \Drupal::request()->query->get('direction', 'ASC'));
    return in_array($direction, ['ASC', 'DESC'], TRUE) ? $direction : 'ASC';
  }

  private static function compareSortableValues(mixed $value_a, mixed $value_b): int {
    if (is_numeric($value_a) && is_numeric($value_b)) {
      return (float) $value_a <=> (float) $value_b;
    }

    return strnatcasecmp((string) $value_a, (string) $value_b);
  }

}
