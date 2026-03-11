<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity\Traits;

trait DhcrLabelTrait {

  public function label(): string {
    // Prefer "title" field if present.
    if ($this->hasField('title') && !$this->get('title')->isEmpty()) {
      return (string) $this->get('title')->value;
    }
    // Fallback to "name".
    if ($this->hasField('name') && !$this->get('name')->isEmpty()) {
      return (string) $this->get('name')->value;
    }
    return parent::label();
  }

}
