<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

final class DhcrGenericListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');
    $header['changed'] = $this->t('Changed');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $row['id'] = $entity->id();
    $row['label'] = $entity->toLink();
    $row['changed'] = $entity->hasField('changed') ? \Drupal::service('date.formatter')->format((int) $entity->get('changed')->value, 'short') : '';
    return $row + parent::buildRow($entity);
  }

}
