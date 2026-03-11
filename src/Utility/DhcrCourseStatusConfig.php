<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Utility;

final class DhcrCourseStatusConfig {

  public static function yellowDate(): int {
    return strtotime('2025-03-04 13:38:24 Europe/Vienna') ?: 0;
  }

  public static function redDate(): int {
    return strtotime('2024-11-04 13:38:24 Europe/Vienna') ?: 0;
  }

  public static function archiveDate(): int {
    return strtotime('2024-03-04 13:38:24 Europe/Vienna') ?: 0;
  }

}
