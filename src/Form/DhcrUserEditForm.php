<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    $profile = $this->loadProfile((int) $user->id());
    $legacy = $this->loadLegacyUserData($user);
    $institution_options = $this->institutionOptions();
    $moderated_country = $this->countryLabel((int) $legacy['country_id']);

    $form['#attached']['library'][] = 'dhcr_backend/admin_user_edit';
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
      '#default_value' => (int) ($profile?->get('institution')->target_id ?? 0) ?: '',
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
    ];
    $form['admin_note'] = [
      '#type' => 'markup',
      '#markup' => '<p class="dhcr-user-edit-form__note"><strong>'
        . $this->t('Note: Please always check or uncheck both options.')
        . '</strong></p>',
      '#weight' => -87,
    ];
    $form['is_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Administrator rights'),
      '#default_value' => (int) $legacy['is_admin'],
      '#weight' => -86,
    ];
    $form['user_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User admin'),
      '#default_value' => (int) $legacy['user_admin'],
      '#weight' => -85,
    ];

    $form['moderator_heading'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('Moderator options') . '</h3>',
      '#weight' => -84,
    ];
    $form['user_role_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Moderator rights'),
      '#options' => [
        1 => $this->t('Old value - please change'),
        2 => $this->t('Yes'),
        3 => $this->t('No'),
      ],
      '#default_value' => (int) $legacy['user_role_id'],
      '#weight' => -83,
    ];
    $form['moderated_country'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Moderated country') . ': ' . ($moderated_country !== '' ? $moderated_country : '-') . '</p>',
      '#weight' => -82,
    ];

    $form['actions']['submit']['#value'] = $this->t('Update User');
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
    $is_admin = (int) ((bool) $form_state->getValue('is_admin'));
    $user_admin = (int) ((bool) $form_state->getValue('user_admin'));
    $user_role_id = (int) $form_state->getValue('user_role_id');

    $user->setEmail($mail);
    $this->applyLegacyRoles($user, $user_role_id, $is_admin, $user_admin);
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

  private function institutionOptions(): array {
    $storage = $this->entityTypeManager->getStorage('dhcr_institution');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('name', 'ASC')
      ->execute();

    $options = [];
    if ($ids) {
      foreach ($storage->loadMultiple($ids) as $institution) {
        $options[(int) $institution->id()] = (string) $institution->label();
      }
    }
    return $options;
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
      'user_role_id' => $role_id,
      'country_id' => (int) ($this->userData->get($module, $uid, 'legacy_country_id') ?? 0),
    ];
  }

  private function guessLegacyRoleId(UserInterface $user): int {
    if ($user->hasRole('administrator')) {
      return 1;
    }
    if ($user->hasRole('moderator')) {
      return 2;
    }
    return 3;
  }

  private function countryLabel(int $country_id): string {
    if ($country_id <= 0) {
      return '';
    }
    $country = $this->entityTypeManager->getStorage('dhcr_country')->load($country_id);
    return $country ? (string) $country->label() : '';
  }

  private function applyLegacyRoles(UserInterface $user, int $user_role_id, int $is_admin, int $user_admin): void {
    foreach (['content_editor', 'contributor', 'moderator', 'administrator'] as $managed_role) {
      if ($user->hasRole($managed_role)) {
        $user->removeRole($managed_role);
      }
    }

    $admin_selected = ($user_role_id === 1) || $is_admin === 1 || $user_admin === 1;
    $moderator_selected = $user_role_id === 2;

    if ($admin_selected) {
      $user->addRole('administrator');
    }
    if ($moderator_selected) {
      $user->addRole('moderator');
    }
    if (!$admin_selected && !$moderator_selected) {
      $user->addRole('contributor');
    }
  }

}
