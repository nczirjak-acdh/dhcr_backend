<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dhcr_backend\Access\DhcrCountryScope;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DhcrUserEditForm extends FormBase {

  private EntityTypeManagerInterface $entityTypeManager;

  private UserDataInterface $userData;

  public static function create(ContainerInterface $container): self {
    $instance = new self();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->userData = $container->get('user.data');
    return $instance;
  }

  public function getFormId(): string {
    return 'dhcr_backend_user_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL): array {
    if (!$user) {
      $form['message'] = ['#markup' => (string) $this->t('User not found.')];
      return $form;
    }
    if (!$this->currentUser()->hasPermission('administer_dhcr_global_settings') && !DhcrCountryScope::matchesUser($this->currentUser(), $user)) {
      throw new AccessDeniedHttpException('This user belongs to another country.');
    }
    if ($user->hasRole('administrator') && !$this->currentUser()->hasPermission('administer permissions')) {
      throw new AccessDeniedHttpException('Only a Drupal administrator can edit another Drupal administrator.');
    }

    $profile = $this->loadProfile((int) $user->id());
    $legacy = $this->loadLegacyUserData($user);
    $institution_countries = [];
    $institution_options = $this->institutionOptions($institution_countries);
    $selected_institution_id = (int) ($profile?->get('institution')->target_id ?? 0);
    $moderated_country = $institution_countries[$selected_institution_id] ?? '';
    $is_global_admin = $this->currentUser()->hasPermission('administer_dhcr_global_settings');
    $can_manage_drupal_admins = $this->currentUser()->hasPermission('administer permissions');

    $form['#attached']['library'][] = 'dhcr_backend/admin_user_edit';
    $form['#attached']['drupalSettings']['dhcrUserEdit']['institutionCountries'] = $institution_countries;
    $form['#attributes']['class'][] = 'dhcr-user-edit-form';

    $form['user_id'] = [
      '#type' => 'hidden',
      '#value' => (int) $user->id(),
    ];

    $email_verified_text = ((int) $legacy['email_verified'] === 1) ? (string) $this->t('Yes') : (string) $this->t('No');
    $email_verified_class = ((int) $legacy['email_verified'] === 1) ? 'is-yes' : 'is-no';
    $password_set_text = ((int) $legacy['password_set'] === 1) ? (string) $this->t('Yes') : (string) $this->t('No');
    $password_set_class = ((int) $legacy['password_set'] === 1) ? 'is-yes' : 'is-no';
    $approved_text = ((int) $legacy['approved'] === 1) ? (string) $this->t('Yes') : (string) $this->t('No');
    $approved_class = ((int) $legacy['approved'] === 1) ? 'is-yes' : 'is-no';

    $form['status_heading'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('Account Status') . '</h3>',
      '#weight' => -98,
    ];
    $form['status_lines'] = [
      '#type' => 'markup',
      '#markup' => '<div class="dhcr-user-edit-form__status-lines">'
        . $this->t('Email Verified') . ': <span class="' . $email_verified_class . '">' . $email_verified_text . '</span><br>'
        . $this->t('Password Set') . ': <span class="' . $password_set_class . '">' . $password_set_text . '</span><br>'
        . $this->t('Approved') . ': <span class="' . $approved_class . '">' . $approved_text . '</span>'
        . '</div>',
      '#weight' => -97,
    ];

    $form['details_heading'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('Details') . '</h3>',
      '#weight' => -96,
    ];

    $form['academic_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Academic Title'),
      '#default_value' => (string) ($profile?->get('academic_title')->value ?? ''),
      '#maxlength' => 255,
      '#weight' => -95,
    ];
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => (string) ($profile?->get('first_name')->value ?? ''),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#weight' => -94,
    ];
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => (string) ($profile?->get('last_name')->value ?? ''),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#weight' => -93,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#default_value' => (string) $user->getEmail(),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#weight' => -92,
    ];
    $form['new_password'] = [
      '#type' => 'password_confirm',
      '#title' => $this->t('Set new password'),
      '#required' => FALSE,
      '#description' => $this->t('Leave empty to keep the current password.'),
      '#weight' => -91.5,
    ];
    $form['mail_list'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Contributor Mailing List Subscription'),
      '#default_value' => (int) $legacy['mail_list'],
      '#weight' => -91,
    ];
    $form['institution_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Institution'),
      '#options' => $institution_options,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $selected_institution_id ?: '',
      '#weight' => -90,
    ];
    $form['about'] = [
      '#type' => 'textarea',
      '#title' => $this->t('About'),
      '#default_value' => (string) $legacy['about'],
      '#rows' => 5,
      '#weight' => -89,
    ];

    $form['admin_heading'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('Administrator options') . '</h3>',
      '#weight' => -88,
      '#access' => $can_manage_drupal_admins,
    ];
    $form['admin_note'] = [
      '#type' => 'markup',
      '#markup' => '<p class="dhcr-user-edit-form__note"><strong>'
        . $this->t('Note: Please always check or uncheck both options.')
        . '</strong></p>',
      '#weight' => -87,
      '#access' => $can_manage_drupal_admins,
    ];
    $form['is_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Administrator rights'),
      '#default_value' => (int) $legacy['is_admin'],
      '#weight' => -86,
      '#access' => $can_manage_drupal_admins,
    ];
    $form['user_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User admin'),
      '#default_value' => (int) $legacy['user_admin'],
      '#weight' => -85,
      '#access' => $can_manage_drupal_admins,
    ];

    $form['moderator_heading'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('Moderator options') . '</h3>',
      '#weight' => -84,
      '#access' => $is_global_admin,
    ];
    $form['user_role_id'] = [
      '#type' => 'select',
      '#title' => $this->t('DHCR role'),
      '#options' => ($can_manage_drupal_admins ? [
        1 => $this->t('Drupal Administrator (full site access)'),
      ] : []) + [
        2 => $this->t('National Moderator'),
        3 => $this->t('Course Contributor'),
        4 => $this->t('CR Administrator (DHCR only)'),
      ],
      '#default_value' => (int) $legacy['user_role_id'],
      '#weight' => -83,
      '#access' => $is_global_admin,
    ];
    $form['moderated_country'] = [
      '#type' => 'container',
      '#weight' => -82,
      '#access' => $is_global_admin,
      '#attributes' => [
        'class' => ['dhcr-user-edit-form__moderated-country'],
        'data-dhcr-moderated-country' => TRUE,
        'hidden' => (int) $legacy['user_role_id'] !== 2,
      ],
    ];
    $form['moderated_country']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this->t('Moderated country') . ': ',
      '#attributes' => ['class' => ['dhcr-user-edit-form__moderated-country-label']],
    ];
    $form['moderated_country']['value'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $moderated_country !== '' ? $moderated_country : '-',
      '#attributes' => [
        'class' => ['dhcr-user-edit-form__moderated-country-value'],
        'data-dhcr-moderated-country-value' => TRUE,
      ],
    ];
    $form['national_moderator_heading'] = [
      '#type' => 'markup',
      '#markup' => '<div class="dhcr-user-edit-form__national-moderator">'
        . '<h3>' . $this->t('National Moderator List') . '</h3>'
        . '<p class="dhcr-user-edit-form__national-note">' . $this->t('Note: Please first check/update the following fields: First Name, Last Name, Email Address, Institution, Country (based on institution), About, Profile Photo. And then also check the box below when assigning moderator rights.') . '</p>'
        . '</div>',
      '#weight' => -81,
      '#access' => $is_global_admin,
    ];
    $form['national_moderator_list'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show this user in the National Moderators List'),
      '#default_value' => (int) $legacy['national_moderator_list'],
      '#weight' => -80,
      '#access' => $is_global_admin,
      '#attributes' => [
        'class' => ['dhcr-user-edit-form__national-checkbox'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 100,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update User'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $mail = trim((string) $form_state->getValue('email'));
    $uid = (int) $form_state->getValue('user_id');
    $existing = user_load_by_mail($mail);
    if ($existing && (int) $existing->id() !== $uid) {
      $form_state->setErrorByName('email', $this->t('Email address is already used by another account.'));
    }
    if (!$this->currentUser()->hasPermission('administer permissions') && (int) $form_state->getValue('user_role_id') === 1) {
      $form_state->setErrorByName('user_role_id', $this->t('You cannot assign the full Drupal Administrator role.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $uid = (int) $form_state->getValue('user_id');
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if (!$user instanceof UserInterface) {
      $this->messenger()->addError($this->t('User not found.'));
      return;
    }

    $mail = trim((string) $form_state->getValue('email'));
    $first_name = trim((string) $form_state->getValue('first_name'));
    $last_name = trim((string) $form_state->getValue('last_name'));
    $academic_title = trim((string) $form_state->getValue('academic_title'));
    $institution_id = (int) $form_state->getValue('institution_id');
    $about = (string) $form_state->getValue('about');
    $mail_list = (int) ((bool) $form_state->getValue('mail_list'));
    $password_value = $form_state->getValue('new_password', '');
    // Drupal 11 converts a validated password_confirm value from the original
    // pass1/pass2 array into a single password string.
    $new_password = is_array($password_value)
      ? (string) ($password_value['pass1'] ?? '')
      : (string) $password_value;
    $is_global_admin = $this->currentUser()->hasPermission('administer_dhcr_global_settings');
    $legacy = $this->loadLegacyUserData($user);
    $is_admin = $is_global_admin ? (int) ((bool) $form_state->getValue('is_admin')) : (int) $legacy['is_admin'];
    $user_admin = $is_global_admin ? (int) ((bool) $form_state->getValue('user_admin')) : (int) $legacy['user_admin'];
    $user_role_id = $is_global_admin ? (int) $form_state->getValue('user_role_id') : (int) $legacy['user_role_id'];
    $national_moderator_list = $is_global_admin ? (int) ((bool) $form_state->getValue('national_moderator_list')) : (int) $legacy['national_moderator_list'];

    $user->setEmail($mail);
    if ($new_password !== '') {
      $user->setPassword($new_password);
    }
    if ($is_global_admin) {
      $this->applyLegacyRoles($user, $user_role_id, $is_admin, $user_admin);
    }
    $user->save();

    $profile_storage = $this->entityTypeManager->getStorage('dhcr_contributor_profile');
    $profile = $this->loadProfile($uid);
    if (!$profile) {
      $profile = $profile_storage->create([
        'name' => trim($first_name . ' ' . $last_name),
        'user' => $uid,
        'email' => $mail,
      ]);
    }

    $profile->set('name', trim($first_name . ' ' . $last_name));
    $profile->set('email', $mail);
    $profile->set('first_name', $first_name);
    $profile->set('last_name', $last_name);
    $profile->set('academic_title', $academic_title);
    $profile->set('institution', $institution_id > 0 ? $institution_id : NULL);
    $profile->set('moderator', $user->hasRole('moderator') ? 1 : 0);
    $profile->save();

    $module = 'dhcr_backend';
    $this->userData->set($module, $uid, 'legacy_mail_list', $mail_list);
    $this->userData->set($module, $uid, 'legacy_about', $about);
    $this->userData->set($module, $uid, 'legacy_is_admin', $is_admin);
    $this->userData->set($module, $uid, 'legacy_user_admin', $user_admin);
    $this->userData->set($module, $uid, 'legacy_user_role_id', $user_role_id);
    $this->userData->set($module, $uid, 'legacy_national_moderator_list', $national_moderator_list);
    $this->userData->set($module, $uid, 'legacy_country_id', $this->institutionCountryId($institution_id));
    // Editing an address in this administrator-controlled form verifies it.
    $this->userData->set($module, $uid, 'legacy_email_verified', 1);
    if ($new_password !== '') {
      $this->userData->set($module, $uid, 'legacy_password_set', 1);
    }

    $this->messenger()->addStatus($this->t('User updated.'));
    $form_state->setRedirect('dhcr_backend.all_users');
  }

  private function loadProfile(int $uid) {
    $storage = $this->entityTypeManager->getStorage('dhcr_contributor_profile');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user', $uid)
      ->range(0, 1)
      ->execute();
    if (!$ids) {
      return NULL;
    }
    return $storage->load((int) reset($ids));
  }

  private function institutionOptions(array &$institution_countries = []): array {
    $storage = $this->entityTypeManager->getStorage('dhcr_institution');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('name', 'ASC');
    if (!$this->currentUser()->hasPermission('administer_dhcr_global_settings')) {
      $country_id = DhcrCountryScope::countryId($this->currentUser());
      $query->condition('country', $country_id > 0 ? $country_id : -1);
    }
    $ids = $query->execute();

    $options = [];
    if ($ids) {
      foreach ($storage->loadMultiple($ids) as $institution) {
        $institution_id = (int) $institution->id();
        $options[$institution_id] = (string) $institution->label();
        $country = $institution->get('country')->entity;
        $institution_countries[$institution_id] = $country ? (string) $country->label() : '';
      }
    }
    return $options;
  }

  private function institutionCountryId(int $institution_id): int {
    if ($institution_id <= 0) {
      return 0;
    }
    $institution = $this->entityTypeManager->getStorage('dhcr_institution')->load($institution_id);
    return (int) ($institution?->get('country')->target_id ?? 0);
  }

  private function loadLegacyUserData(UserInterface $user): array {
    $uid = (int) $user->id();
    $module = 'dhcr_backend';

    $stored_role_id = $this->userData->get($module, $uid, 'legacy_user_role_id');
    $role_id = (int) $stored_role_id;
    if ($role_id === 0) {
      $role_id = $this->guessLegacyRoleId($user);
    }

    $password_set = $this->userData->get($module, $uid, 'legacy_password_set');
    if ($password_set === NULL) {
      $password_set = $user->getPassword() ? 1 : 0;
    }

    return [
      'email_verified' => (int) ($this->userData->get($module, $uid, 'legacy_email_verified') ?? 0),
      'password_set' => (int) $password_set,
      'approved' => (int) ($this->userData->get($module, $uid, 'legacy_approved') ?? 0),
      'mail_list' => (int) ($this->userData->get($module, $uid, 'legacy_mail_list') ?? 0),
      'about' => (string) ($this->userData->get($module, $uid, 'legacy_about') ?? ''),
      'is_admin' => (int) ($this->userData->get($module, $uid, 'legacy_is_admin') ?? ($user->hasRole('administrator') ? 1 : 0)),
      'user_admin' => (int) ($this->userData->get($module, $uid, 'legacy_user_admin') ?? 0),
      'national_moderator_list' => (int) ($this->userData->get($module, $uid, 'legacy_national_moderator_list') ?? 0),
      'user_role_id' => $role_id,
      'country_id' => (int) ($this->userData->get($module, $uid, 'legacy_country_id') ?? 0),
    ];
  }

  private function guessLegacyRoleId(UserInterface $user): int {
    if ($user->hasRole('administrator')) {
      return 1;
    }
    if ($user->hasRole('cr_admin')) {
      return 4;
    }
    if ($user->hasRole('moderator')) {
      return 2;
    }
    return 3;
  }

  private function applyLegacyRoles(UserInterface $user, int $user_role_id, int $is_admin, int $user_admin): void {
    foreach (['content_editor', 'contributor', 'moderator', 'cr_admin', 'administrator'] as $managed_role) {
      if ($user->hasRole($managed_role)) {
        $user->removeRole($managed_role);
      }
    }

    $admin_selected = ($user_role_id === 1) || $is_admin === 1 || $user_admin === 1;
    $moderator_selected = $user_role_id === 2;
    $cr_admin_selected = $user_role_id === 4;

    if ($admin_selected) {
      $user->addRole('administrator');
    }
    if ($moderator_selected) {
      $user->addRole('moderator');
    }
    if ($cr_admin_selected) {
      $user->addRole('cr_admin');
    }
    if (!$admin_selected && !$moderator_selected && !$cr_admin_selected) {
      $user->addRole('contributor');
    }
  }

}
