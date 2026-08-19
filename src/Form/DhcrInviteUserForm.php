<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dhcr_backend\Service\DhcrMailManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class DhcrInviteUserForm extends DhcrContentEntityForm {

  private DhcrMailManager $dhcrMailManager;

  private const LOCALIZATION_NAMES = [
    'English',
    'German',
    'Finnish',
    'Czech',
    'Hungarian',
    'Greek',
    'French',
  ];

  public function __construct(
    ...$args
  ) {
    parent::__construct(...$args);
  }

  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->dhcrMailManager = $container->get('dhcr_backend.mail_manager');
    return $instance;
  }

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'dhcr_backend/admin_contributor_network';
    $form['#attributes']['class'][] = 'dhcr-invite-user-form';

    $form['dhcr_invite_heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-invite-user-form__heading">+ ' . $this->t('Invite User') . '</h2>',
    ];

    $form['dhcr_invite_intro'] = [
      '#type' => 'markup',
      '#weight' => -99,
      '#markup' => '<p class="dhcr-invite-user-form__intro">'
        . $this->t('The user will receive an email to set their password and join the DH-Courseregistry.') . '<br>'
        . $this->t('You will receive a BCC of this email.')
        . '</p>',
    ];

    if (isset($form['institution']['widget'][0]['target_id'])) {
      $form['institution']['widget'][0]['target_id']['#type'] = 'select';
      $form['institution']['widget'][0]['target_id']['#title'] = $this->t('Institution*');
      $form['institution']['widget'][0]['target_id']['#empty_option'] = '';
      $form['institution']['widget'][0]['target_id']['#required'] = TRUE;
    }

    if (isset($form['academic_title']['widget'][0]['value'])) {
      $form['academic_title']['widget'][0]['value']['#title'] = $this->t('Academic Title');
    }

    if (isset($form['first_name']['widget'][0]['value'])) {
      $form['first_name']['widget'][0]['value']['#title'] = $this->t('First Name*');
    }

    if (isset($form['last_name']['widget'][0]['value'])) {
      $form['last_name']['widget'][0]['value']['#title'] = $this->t('Last Name*');
    }

    if (isset($form['email']['widget'][0]['value'])) {
      $form['email']['widget'][0]['value']['#title'] = $this->t('Institutional Email Address*');
    }

    if (isset($form['localization']['widget'][0]['target_id'])) {
      $form['localization']['widget'][0]['target_id']['#type'] = 'select';
      $form['localization']['widget'][0]['target_id']['#title'] = $this->t('Choose localization*');
      $form['localization']['widget'][0]['target_id']['#options'] = $this->getLocalizationOptions();
      $form['localization']['widget'][0]['target_id']['#required'] = TRUE;
    }

    $form['dhcr_step_1'] = [
      '#type' => 'markup',
      '#weight' => -50,
      '#markup' => '<div class="dhcr-invite-user-form__step-title">' . $this->t('Step 1: Select an institution for the new user') . '</div>'
        . '<div class="dhcr-invite-user-form__step-copy">' . $this->t('Select an institution from the drop-down list. If the institution is not listed, go to') . ' '
        . '<a href="' . Url::fromRoute('entity.dhcr_institution.add_form')->toString() . '">' . $this->t('Add Institution') . '</a>.</div>',
    ];

    $form['dhcr_step_2'] = [
      '#type' => 'markup',
      '#weight' => 10,
      '#markup' => '<div class="dhcr-invite-user-form__step-title">' . $this->t('Step 2: Enter the personal details of the user') . '</div>',
    ];

    $form['dhcr_step_3'] = [
      '#type' => 'markup',
      '#weight' => 30,
      '#markup' => '<div class="dhcr-invite-user-form__step-title">' . $this->t('Step 3: Personalize the invitation email') . '</div>'
        . '<div class="dhcr-invite-user-form__note-title">' . $this->t('Note for non-English countries') . '</div>'
        . '<div class="dhcr-invite-user-form__step-copy">' . $this->t('Users may respond better to an invitation in their mother language. Although the interface and the metadata in the Course Registry are in English, you have the possibility to localize the invitation message.') . '</div>'
        . '<div class="dhcr-invite-user-form__preview-title">' . $this->t('Preview localized messages') . '</div>'
        . '<div class="dhcr-invite-user-form__preview-list">' . implode('<br>', $this->buildTranslationPreviewLinks()) . '</div>',
    ];

    if (isset($form['name'])) {
      $form['name']['#access'] = FALSE;
    }
    if (isset($form['user'])) {
      $form['user']['#access'] = FALSE;
    }
    if (isset($form['account_enabled'])) {
      $form['account_enabled']['#access'] = FALSE;
    }
    if (isset($form['valid_until'])) {
      $form['valid_until']['#access'] = FALSE;
    }
    if (isset($form['legacy_user_id'])) {
      $form['legacy_user_id']['#access'] = FALSE;
    }

    $this->ensureInviteUserElements($form);
    $form['initial_password'] = [
      '#type' => 'password_confirm',
      '#title' => $this->t('Initial password'),
      '#required' => FALSE,
      '#description' => $this->t('Optional. Leave empty to let the user choose a password using the link in the invitation email.'),
    ];
    $this->groupInviteUserFields($form);

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();

    $first_name = trim((string) $entity->get('first_name')->value);
    $last_name = trim((string) $entity->get('last_name')->value);
    $email = trim((string) $entity->get('email')->value);
    $full_name = trim($first_name . ' ' . $last_name);
    $password_value = $form_state->getValue('initial_password', '');
    // Drupal 11 converts a validated password_confirm value from the original
    // pass1/pass2 array into a single password string.
    $initial_password = is_array($password_value)
      ? (string) ($password_value['pass1'] ?? '')
      : (string) $password_value;

    $entity->set('name', $full_name !== '' ? $full_name : $email);
    $entity->set('valid_until', strtotime('+24 hours'));
    $entity->set('account_enabled', 1);

    $account = user_load_by_mail($email);
    $is_new_account = !$account;
    if (!$account) {
      $username = $full_name !== '' ? $full_name : $email;
      $account = User::create([
        'name' => $username,
        'mail' => $email,
        'status' => 1,
        // Drupal requires a stored password even when the invited user will
        // choose their real password through the one-time login link.
        'pass' => $initial_password !== '' ? $initial_password : bin2hex(random_bytes(32)),
      ]);
      $account->addRole('contributor');
      $account->save();
    }
    else {
      $account_changed = FALSE;
      if (!$account->hasRole('contributor') && !$account->hasRole('moderator') && !$account->hasRole('cr_admin') && !$account->hasRole('administrator')) {
        $account->addRole('contributor');
        $account_changed = TRUE;
      }
      if ($initial_password !== '') {
        $account->setPassword($initial_password);
        $account_changed = TRUE;
      }
      if ($account_changed) {
        $account->save();
      }
    }

    $user_data = \Drupal::service('user.data');
    // An administrator entered the address directly, so no separate address
    // verification step is required.
    $user_data->set('dhcr_backend', (int) $account->id(), 'legacy_email_verified', 1);
    if ($is_new_account) {
      $user_data->set('dhcr_backend', (int) $account->id(), 'legacy_password_set', $initial_password !== '' ? 1 : 0);
      $user_data->set('dhcr_backend', (int) $account->id(), 'legacy_approved', 0);
    }
    elseif ($initial_password !== '') {
      $user_data->set('dhcr_backend', (int) $account->id(), 'legacy_password_set', 1);
    }

    $entity->set('user', $account->id());
    $status = parent::save($form, $form_state);

    $profile_storage = $this->entityTypeManager->getStorage('dhcr_contributor_profile');
    $profile_ids = $profile_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user', $account->id())
      ->range(0, 1)
      ->execute();
    $profile = $profile_ids ? $profile_storage->load(reset($profile_ids)) : $profile_storage->create([]);

    $institution = $entity->get('institution')->target_id;
    $other_org = '';
    if ($institution) {
      $institution_entity = $this->entityTypeManager->getStorage('dhcr_institution')->load($institution);
      $other_org = $institution_entity ? (string) $institution_entity->label() : '';
    }

    $profile->set('name', $entity->label());
    $profile->set('user', $account->id());
    $profile->set('email', $email);
    $profile->set('first_name', $first_name);
    $profile->set('last_name', $last_name);
    $profile->set('academic_title', (string) $entity->get('academic_title')->value);
    $profile->set('institution', $institution);
    $profile->set('other_organisation', $other_org);
    $profile->set('enabled', 1);
    if ($profile->isNew()) {
      $profile->set('moderator', 0);
    }
    $profile->save();

    if ($this->dhcrMailManager->sendInvitation($entity, $account)) {
      $this->messenger()->addStatus($this->t('Invitation sent to %mail.', ['%mail' => $email]));
    }
    else {
      $this->messenger()->addError($this->t('The invitation was saved, but the email to %mail could not be sent. Check the DHCR logs.', ['%mail' => $email]));
    }
    $form_state->setRedirect('dhcr_backend.pending_invitations');

    return $status;
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['submit'])) {
      $actions['submit']['#value'] = $this->t('Send Invitation');
      $actions['submit']['#attributes']['class'][] = 'button--dhcr-outline';
    }
    return $actions;
  }

  private function getLocalizationOptions(): array {
    $storage = $this->entityTypeManager->getStorage('dhcr_language');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('name', self::LOCALIZATION_NAMES, 'IN')
      ->execute();

    $options = [];
    $languages_by_name = [];
    foreach ($storage->loadMultiple($ids) as $language) {
      $languages_by_name[(string) $language->label()] = $language;
    }

    foreach (self::LOCALIZATION_NAMES as $name) {
      if (isset($languages_by_name[$name])) {
        $language = $languages_by_name[$name];
        $options[(string) $language->id()] = (string) $language->label();
      }
    }
    return $options;
  }

  private function ensureInviteUserElements(array &$form): void {
    $entity = $this->getEntity();

    if (!isset($form['institution'])) {
      $form['institution'] = [
        '#type' => 'select',
        '#title' => $this->t('Institution*'),
        '#options' => $this->getInstitutionOptions(),
        '#empty_option' => '',
        '#default_value' => (string) ($entity->get('institution')->target_id ?? ''),
        '#required' => TRUE,
        '#parents' => ['institution'],
      ];
    }

    foreach ([
      'academic_title' => ['title' => 'Academic Title', 'type' => 'textfield', 'required' => FALSE],
      'first_name' => ['title' => 'First Name*', 'type' => 'textfield', 'required' => TRUE],
      'last_name' => ['title' => 'Last Name*', 'type' => 'textfield', 'required' => TRUE],
      'email' => ['title' => 'Institutional Email Address*', 'type' => 'email', 'required' => TRUE],
    ] as $field_name => $settings) {
      if (isset($form[$field_name])) {
        continue;
      }

      $form[$field_name] = [
        '#type' => $settings['type'],
        '#title' => $this->t($settings['title']),
        '#default_value' => (string) ($entity->get($field_name)->value ?? ''),
        '#required' => $settings['required'],
        '#parents' => [$field_name],
      ];
    }

    if (!isset($form['localization'])) {
      $options = $this->getLocalizationOptions();
      $default_value = (string) ($entity->get('localization')->target_id ?? '');
      if ($default_value === '') {
        $default_value = (string) array_search('English', $options, TRUE);
      }

      $form['localization'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose localization*'),
        '#options' => $options,
        '#default_value' => $default_value,
        '#required' => TRUE,
        '#parents' => ['localization'],
      ];
    }
  }

  private function getInstitutionOptions(): array {
    $storage = $this->entityTypeManager->getStorage('dhcr_institution');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('name', 'ASC')
      ->execute();

    $options = [];
    foreach ($storage->loadMultiple($ids) as $institution) {
      $options[(string) $institution->id()] = (string) $institution->label();
    }

    return $options;
  }

  private function buildTranslationPreviewLinks(): array {
    $translations = $this->loadInviteTranslationsByLanguage();
    $links = [];

    foreach (self::LOCALIZATION_NAMES as $name) {
      if (!isset($translations[$name])) {
        $links[] = $name;
        continue;
      }

      $translation = $translations[$name];
      $links[] = '<a href="' . $translation->toUrl('edit-form')->toString() . '">' . $name . '</a>';
    }

    return $links;
  }

  private function loadInviteTranslationsByLanguage(): array {
    $storage = $this->entityTypeManager->getStorage('dhcr_invite_translation');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->execute();

    $translations = [];
    foreach ($storage->loadMultiple($ids) as $translation) {
      $language = $translation->get('language')->entity;
      if ($language) {
        $translations[(string) $language->label()] = $translation;
      }
    }

    return $translations;
  }

  private function groupInviteUserFields(array &$form): void {
    $form['invite_user'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Invite User'),
      '#weight' => -90,
      '#attributes' => [
        'class' => ['dhcr-invite-user-form__fieldset'],
      ],
    ];

    $items = [
      'dhcr_step_1' => -30,
      'institution' => -29,
      'dhcr_step_2' => -20,
      'academic_title' => -19,
      'first_name' => -18,
      'last_name' => -17,
      'email' => -16,
      'initial_password' => -15,
      'dhcr_step_3' => -10,
      'localization' => -9,
    ];

    foreach ($items as $key => $weight) {
      if (!isset($form[$key])) {
        continue;
      }

      $form[$key]['#weight'] = $weight;
      $form['invite_user'][$key] = $form[$key];
      unset($form[$key]);
    }
  }
}
