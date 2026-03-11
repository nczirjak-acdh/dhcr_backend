<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Entity\Interface;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

interface CourseInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {}
