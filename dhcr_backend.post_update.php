<?php

declare(strict_types=1);

/**
 * Installs missing DHCR backend entity schemas after interrupted installs.
 */
function dhcr_backend_post_update_install_missing_entity_schemas(array &$sandbox): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $schema = \Drupal::database()->schema();

  $entity_type_ids = [
    'dhcr_country',
    'dhcr_city',
    'dhcr_institution',
    'dhcr_course',
    'dhcr_duration_unit',
    'dhcr_course_type',
    'dhcr_language',
    'dhcr_external_resource',
    'dhcr_faq_question',
    'dhcr_contributor_profile',
    'dhcr_user_invitation',
    'dhcr_invite_translation',
  ];

  foreach ($entity_type_ids as $entity_type_id) {
    $entity_type = $entity_type_manager->getDefinition($entity_type_id, FALSE);
    if (!$entity_type) {
      continue;
    }

    $base_table = (string) $entity_type->getBaseTable();
    if ($base_table !== '' && $schema->tableExists($base_table)) {
      continue;
    }

    $entity_definition_update_manager->installEntityType($entity_type);
  }
}

/**
 * Installs external resource and FAQ entity schemas added after initial install.
 */
function dhcr_backend_post_update_install_supporting_content_entity_schemas(array &$sandbox): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $schema = \Drupal::database()->schema();

  foreach (['dhcr_external_resource', 'dhcr_faq_question', 'dhcr_contributor_profile', 'dhcr_user_invitation', 'dhcr_invite_translation'] as $entity_type_id) {
    $entity_type = $entity_type_manager->getDefinition($entity_type_id, FALSE);
    if (!$entity_type) {
      continue;
    }

    $base_table = (string) $entity_type->getBaseTable();
    if ($base_table !== '' && $schema->tableExists($base_table)) {
      continue;
    }

    $entity_definition_update_manager->installEntityType($entity_type);
  }
}

/**
 * Installs invite translation entity schema.
 */
function dhcr_backend_post_update_install_invite_translation_entity(array &$sandbox): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $schema = \Drupal::database()->schema();

  $entity_type = $entity_type_manager->getDefinition('dhcr_invite_translation', FALSE);
  if (!$entity_type) {
    return;
  }

  $base_table = (string) $entity_type->getBaseTable();
  if ($base_table !== '' && $schema->tableExists($base_table)) {
    return;
  }

  $entity_definition_update_manager->installEntityType($entity_type);
}

/**
 * Seeds default invite translations.
 */
function dhcr_backend_post_update_seed_invite_translations(array &$sandbox): void {
  $storage = \Drupal::entityTypeManager()->getStorage('dhcr_invite_translation');
  $schema = \Drupal::database()->schema();

  if (!$schema->tableExists('dhcr_invite_translation')) {
    return;
  }

  $existing = $storage->getQuery()
    ->accessCheck(FALSE)
    ->range(0, 1)
    ->execute();
  if ($existing) {
    return;
  }

  $language_storage = \Drupal::entityTypeManager()->getStorage('dhcr_language');
  $translations = dhcr_backend_get_legacy_invite_translations();
  if ($translations === []) {
    $translations = dhcr_backend_get_default_invite_translations();
  }

  foreach ($translations as $item) {
    $language = dhcr_backend_ensure_invite_translation_language($language_storage, $item['language']);
    if (!$language) {
      continue;
    }

    $storage->create([
      'sort_order' => $item['sort_order'],
      'language' => $language->id(),
      'subject' => $item['subject'],
      'message_body' => ['value' => $item['message_body']],
      'published' => $item['published'],
      'created' => $item['created'],
      'changed' => $item['changed'],
    ])->save();
  }
}

/**
 * Ensures a language entity exists for invite translation defaults.
 */
function dhcr_backend_ensure_invite_translation_language($language_storage, string $name) {
  $matches = $language_storage->loadByProperties(['name' => $name]);
  if ($matches) {
    return reset($matches);
  }

  $language = $language_storage->create([
    'name' => $name,
    'iso' => '',
  ]);
  $language->save();
  return $language;
}

/**
 * Loads invite translations from the legacy import database when available.
 */
function dhcr_backend_get_legacy_invite_translations(): array {
  try {
    $options = \Drupal::database()->getConnectionOptions();
    $dsn = sprintf(
      'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
      $options['host'] ?? '127.0.0.1',
      $options['port'] ?? '3306',
      'dhcr_legacy_import'
    );

    $pdo = new \PDO($dsn, $options['username'] ?? '', $options['password'] ?? '', [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    $statement = $pdo->query("
      SELECT
        it.id,
        l.name AS language_name,
        it.sortOrder AS sort_order,
        it.subject,
        it.messageBody AS message_body,
        it.created,
        it.updated,
        it.active
      FROM invite_translations it
      INNER JOIN languages l ON l.id = it.language_id
      ORDER BY it.sortOrder ASC
    ");

    $rows = [];
    foreach ($statement->fetchAll() as $row) {
      $created = dhcr_backend_parse_legacy_datetime((string) $row['created'], \Drupal::time()->getRequestTime());
      $changed = dhcr_backend_parse_legacy_datetime((string) $row['updated'], $created);

      $rows[] = [
        'id' => (int) $row['id'],
        'language' => (string) $row['language_name'],
        'sort_order' => (int) $row['sort_order'],
        'subject' => (string) $row['subject'],
        'message_body' => (string) $row['message_body'],
        'published' => (int) $row['active'],
        'created' => $created,
        'changed' => $changed,
      ];
    }

    return $rows;
  }
  catch (\Throwable) {
    return [];
  }
}

/**
 * Fallback invite translations used when the legacy table is unavailable.
 */
function dhcr_backend_get_default_invite_translations(): array {
  $default_message = <<<'TEXT'
Dear colleague,

We would like to invite you to include your teaching activities in the Digital Humanities Course Registry (DHCR). The activities could consist of any course or module in a BA, MA, or PhD programme including summer schools or continuing education that combine humanities content with digital or computational components.

The mission of the DH course registry is to provide an overview and a discovery platform of the Digital Humanities courses taught at universities and research institutes within your home country and beyond. This community-driven initiative endorses the principle that sharing knowledge is in the best interest of students, lecturers and researchers. Various stakeholders benefit from this overview in the following way:

-Lecturers and researchers can promote their teaching and educational activities beyond the usual university networks
-Students can identify DH programmes or exchange opportunities in their home country or abroad
-Programme administrators can use the platform to promote and facilitate students and staff exchange

To add your teaching and educational activities to the registry, please proceed as follows:
1. Set your password
2. Go to Administrate Courses
3. Click Add course
4. Fill in the details of your course

The data that you provide will be reviewed by the national moderator who has the task of monitoring and curating the DHCR in your country.

We sincerely hope you will contribute to our effort to expand the knowledge on how technology can support research in the humanities and social sciences.

*Click the link below to set a password and access your account:*
-passwordlink-
The link is valid for 24 hours.

Best wishes and thank you for your effort,

-fullname- (moderator) and the Course Registry Team
TEXT;

  return [
    ['id' => 1, 'language' => 'English', 'sort_order' => 1, 'subject' => 'Join the Digital Humanities Course Registry', 'message_body' => $default_message, 'published' => 1, 'created' => \Drupal::time()->getRequestTime(), 'changed' => \Drupal::time()->getRequestTime()],
    ['id' => 2, 'language' => 'German', 'sort_order' => 2, 'subject' => 'Einladung: Listen Sie Ihre Kurse in der Digital Humanities Course Registry', 'message_body' => $default_message, 'published' => 1, 'created' => \Drupal::time()->getRequestTime(), 'changed' => \Drupal::time()->getRequestTime()],
    ['id' => 3, 'language' => 'Finnish', 'sort_order' => 3, 'subject' => 'kutsu listata kurssit', 'message_body' => $default_message, 'published' => 1, 'created' => \Drupal::time()->getRequestTime(), 'changed' => \Drupal::time()->getRequestTime()],
    ['id' => 4, 'language' => 'Czech', 'sort_order' => 4, 'subject' => 'pozvánka na vypsání kurzů', 'message_body' => $default_message, 'published' => 1, 'created' => \Drupal::time()->getRequestTime(), 'changed' => \Drupal::time()->getRequestTime()],
    ['id' => 5, 'language' => 'Hungarian', 'sort_order' => 5, 'subject' => 'meghívás a tanfolyamok listázására', 'message_body' => $default_message, 'published' => 1, 'created' => \Drupal::time()->getRequestTime(), 'changed' => \Drupal::time()->getRequestTime()],
    ['id' => 6, 'language' => 'Greek', 'sort_order' => 6, 'subject' => 'πρόσκληση για να καταχωρήσετε τα μαθήματά σας', 'message_body' => $default_message, 'published' => 1, 'created' => \Drupal::time()->getRequestTime(), 'changed' => \Drupal::time()->getRequestTime()],
    ['id' => 7, 'language' => 'French', 'sort_order' => 7, 'subject' => 'invitation à lister vos cours', 'message_body' => $default_message, 'published' => 1, 'created' => \Drupal::time()->getRequestTime(), 'changed' => \Drupal::time()->getRequestTime()],
  ];
}

/**
 * Parses a legacy datetime string safely.
 */
function dhcr_backend_parse_legacy_datetime(string $value, int $fallback): int {
  $value = trim($value);
  if ($value === '' || str_starts_with($value, '0000-00-00')) {
    return $fallback;
  }

  $timestamp = strtotime($value);
  if ($timestamp === FALSE || $timestamp < 0) {
    return $fallback;
  }

  return $timestamp;
}

/**
 * Ensures DHCR-specific user roles exist.
 */
function dhcr_backend_post_update_ensure_dhcr_user_roles(array &$sandbox): void {
  if (!class_exists('\Drupal\user\Entity\Role')) {
    return;
  }

  $definitions = [
    'contributor' => 'Contributor',
    'moderator' => 'Moderator',
    'administrator' => 'Administrator',
  ];

  foreach ($definitions as $role_id => $label) {
    $role = \Drupal\user\Entity\Role::load($role_id);
    if (!$role) {
      $role = \Drupal\user\Entity\Role::create([
        'id' => $role_id,
        'label' => $label,
      ]);
    }

    if (!$role->hasPermission('administer dhcr backend')) {
      $role->grantPermission('administer dhcr backend');
    }
    $role->save();
  }
}
