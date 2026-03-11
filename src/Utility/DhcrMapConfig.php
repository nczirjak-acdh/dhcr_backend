<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Utility;

use Drupal\Core\Site\Settings;
use Symfony\Component\Yaml\Yaml;

final class DhcrMapConfig {

  private static ?array $config = NULL;

  private static function getConfig(): array {
    if (self::$config !== NULL) {
      return self::$config;
    }

    self::$config = [];
    $config_path = self::getConfigPath();

    if (is_file($config_path)) {
      try {
        $data = Yaml::parseFile($config_path);
        if (is_array($data)) {
          self::$config = $data;
        }
      }
      catch (\Throwable) {
        self::$config = [];
      }
    }

    return self::$config;
  }

  public static function getMapboxToken(): string {
    static $token;

    if ($token !== NULL) {
      return $token;
    }

    $token = '';
    $config = self::getConfig();

    if (isset($config['mapbox_access_token']) && is_string($config['mapbox_access_token'])) {
      $token = trim($config['mapbox_access_token']);
    }

    // Temporary fallback so existing environments do not break during the move.
    if ($token === '') {
      $token = (string) Settings::get('dhcr_backend.mapbox_access_token', '');
    }

    return $token;
  }

  public static function getExpirationPeriod(): int {
    $config = self::getConfig();

    if (isset($config['expirationPeriod']) && is_numeric($config['expirationPeriod'])) {
      return max(0, (int) $config['expirationPeriod']);
    }

    return 60 * 60 * 24 * 489;
  }

  public static function getCourseArchiveDateCutoff(): int {
    $config = self::getConfig();
    $now = \Drupal::time()->getRequestTime();

    if (isset($config['courseArchiveDate']) && is_string($config['courseArchiveDate']) && trim($config['courseArchiveDate']) !== '') {
      $timestamp = strtotime(trim($config['courseArchiveDate']), $now);
      if ($timestamp !== FALSE) {
        return (int) $timestamp;
      }
    }

    $fallback = strtotime('-24 months', $now);
    return $fallback !== FALSE ? (int) $fallback : 0;
  }

  public static function getConfigPath(): string {
    $module_path = \Drupal::service('extension.list.module')->getPath('dhcr_backend');
    return DRUPAL_ROOT . '/' . $module_path . '/config/config.yaml';
  }

}
