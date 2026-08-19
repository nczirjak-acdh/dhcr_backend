<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\dhcr_backend\Access\DhcrCountryScope;
use Drupal\dhcr_backend\Entity\UserInvitation;
use Drupal\user\UserInterface;

/**
 * Sends editable DHCR emails and records every attempt in dhcr_logs.
 */
final class DhcrMailManager {

  private const CONFIG_NAME = 'dhcr_backend.email_templates';

  public function __construct(
    private readonly MailManagerInterface $mailManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LanguageManagerInterface $languageManager,
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Sends a configured email template.
   */
  public function sendTemplate(string $template_id, string|array $to, array $tokens = [], array $options = []): bool {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $template = (array) $config->get('templates.' . $template_id);

    if ($template === [] || empty($template['enabled'])) {
      $this->writeLog(FALSE, $template_id, '', '', $options, 'Template is missing or disabled.');
      return FALSE;
    }

    return $this->sendMessage(
      $template_id,
      (string) ($template['subject'] ?? ''),
      (string) ($template['body'] ?? ''),
      $to,
      $tokens,
      $options,
    );
  }

  /**
   * Sends a localized invitation using the existing translation entities.
   */
  public function sendInvitation(UserInvitation $invitation, UserInterface $account): bool {
    $language_id = (int) ($invitation->get('localization')->target_id ?? 0);
    $translation_storage = $this->entityTypeManager->getStorage('dhcr_invite_translation');
    $translation_ids = $translation_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('language', $language_id)
      ->condition('published', 1)
      ->range(0, 1)
      ->execute();

    if (!$translation_ids) {
      $this->writeLog(FALSE, 'invitation', $account->getEmail(), '', [
        'related_user' => (int) $account->id(),
      ], 'No published invitation translation was found.');
      return FALSE;
    }

    $translation = $translation_storage->load((int) reset($translation_ids));
    $inviter = $this->entityTypeManager->getStorage('user')->load((int) $this->currentUser->id());
    $inviter_email = $inviter instanceof UserInterface ? $inviter->getEmail() : '';

    return $this->sendMessage(
      'invitation',
      (string) $translation->get('subject')->value,
      (string) $translation->get('message_body')->value,
      $account->getEmail(),
      [
        'fullname' => $this->fullName($inviter),
        'passwordlink' => user_pass_reset_url($account),
      ],
      [
        'bcc' => $inviter_email,
        'reply_to' => $inviter_email,
        'related_user' => (int) $account->id(),
      ],
    );
  }

  /**
   * Returns active country moderators, falling back to global administrators.
   */
  public function moderatorEmails(int $country_id): array {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $moderator_ids = $user_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'moderator')
      ->execute();

    $emails = [];
    foreach ($user_storage->loadMultiple($moderator_ids) as $moderator) {
      if ($moderator instanceof UserInterface && DhcrCountryScope::userCountryId($moderator) === $country_id) {
        $emails[] = $moderator->getEmail();
      }
    }

    if ($emails !== []) {
      return array_values(array_unique(array_filter($emails)));
    }

    $administrator_ids = $user_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', ['administrator', 'cr_admin'], 'IN')
      ->execute();
    foreach ($user_storage->loadMultiple($administrator_ids) as $administrator) {
      if ($administrator instanceof UserInterface) {
        $emails[] = $administrator->getEmail();
      }
    }

    return array_values(array_unique(array_filter($emails)));
  }

  /**
   * Builds common name tokens for user-facing templates.
   */
  public function userNameTokens(UserInterface $user): array {
    $profile_storage = $this->entityTypeManager->getStorage('dhcr_contributor_profile');
    $profile_ids = $profile_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user', (int) $user->id())
      ->range(0, 1)
      ->execute();
    $profile = $profile_ids ? $profile_storage->load((int) reset($profile_ids)) : NULL;

    return [
      'first_name' => trim((string) ($profile?->get('first_name')->value ?? '')),
      'last_name' => trim((string) ($profile?->get('last_name')->value ?? '')),
      'user_name' => $this->fullName($user),
      'user_email' => $user->getEmail(),
    ];
  }

  /**
   * Sends one message to each recipient and logs each result.
   */
  private function sendMessage(string $template_id, string $subject, string $body, string|array $to, array $tokens, array $options): bool {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $site_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $tokens += ['site_url' => rtrim($site_url, '/')];

    $prefix = (string) ($config->get('subject_prefix') ?? '');
    $subject = $this->replaceTokens($subject, $tokens);
    if ($prefix !== '' && !str_starts_with($subject, $prefix)) {
      $subject = $prefix . $subject;
    }

    $body = $this->replaceTokens($body, $tokens);
    if (($options['append_footer'] ?? TRUE) && trim((string) $config->get('footer')) !== '') {
      $body .= "\n\n\n" . $this->replaceTokens((string) $config->get('footer'), $tokens);
    }

    $recipients = $this->normalizeAddresses($to);
    if ($recipients === []) {
      $this->writeLog(FALSE, $template_id, '', $subject, $options, 'No valid recipient address was supplied.');
      return FALSE;
    }

    $sender = trim((string) ($options['from'] ?? $config->get('sender') ?? ''));
    if ($sender === '') {
      $sender = trim((string) $this->configFactory->get('system.site')->get('mail'));
    }
    $reply_to = trim((string) ($options['reply_to'] ?? $config->get('reply_to') ?? $sender));
    $cc = array_values(array_unique(array_merge(
      $this->normalizeAddresses((string) ($config->get('default_cc') ?? '')),
      $this->normalizeAddresses($options['cc'] ?? []),
    )));
    $bcc = $this->normalizeAddresses($options['bcc'] ?? []);

    $all_sent = TRUE;
    foreach ($recipients as $recipient) {
      $params = [
        'subject' => $subject,
        'body' => $body,
        'from' => $sender,
        'cc' => $cc,
        'bcc' => $bcc,
      ];

      try {
        $result = $this->mailManager->mail(
          'dhcr_backend',
          'template',
          $recipient,
          $this->languageManager->getDefaultLanguage()->getId(),
          $params,
          $reply_to !== '' ? $reply_to : NULL,
          TRUE,
        );
        $sent = !empty($result['result']);
        $this->writeLog($sent, $template_id, $recipient, $subject, $options, $sent ? '' : 'Drupal mail backend returned failure.');
        $all_sent = $all_sent && $sent;
      }
      catch (\Throwable $exception) {
        $all_sent = FALSE;
        $this->writeLog(FALSE, $template_id, $recipient, $subject, $options, $exception->getMessage());
      }
    }

    return $all_sent;
  }

  private function replaceTokens(string $value, array $tokens): string {
    $replacements = [];
    foreach ($tokens as $key => $replacement) {
      if (!is_scalar($replacement) && $replacement !== NULL) {
        continue;
      }
      $replacement = (string) $replacement;
      $replacements['{{ ' . $key . ' }}'] = $replacement;
      $replacements['{{' . $key . '}}'] = $replacement;
      $replacements['-' . $key . '-'] = $replacement;
    }
    return strtr($value, $replacements);
  }

  private function normalizeAddresses(string|array $addresses): array {
    if (is_string($addresses)) {
      $addresses = preg_split('/[,;]+/', $addresses) ?: [];
    }
    $normalized = [];
    foreach ($addresses as $address) {
      $address = trim((string) $address);
      if ($address !== '' && filter_var($address, FILTER_VALIDATE_EMAIL)) {
        $normalized[] = $address;
      }
    }
    return array_values(array_unique($normalized));
  }

  private function fullName(?UserInterface $user): string {
    if (!$user) {
      return '';
    }
    $tokens = $this->userNameTokensWithoutRecursion($user);
    $name = trim(implode(' ', array_filter([
      $tokens['academic_title'],
      $tokens['first_name'],
      $tokens['last_name'],
    ])));
    return $name !== '' ? $name : $user->getDisplayName();
  }

  private function userNameTokensWithoutRecursion(UserInterface $user): array {
    $profile_storage = $this->entityTypeManager->getStorage('dhcr_contributor_profile');
    $profile_ids = $profile_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user', (int) $user->id())
      ->range(0, 1)
      ->execute();
    $profile = $profile_ids ? $profile_storage->load((int) reset($profile_ids)) : NULL;
    return [
      'academic_title' => trim((string) ($profile?->get('academic_title')->value ?? '')),
      'first_name' => trim((string) ($profile?->get('first_name')->value ?? '')),
      'last_name' => trim((string) ($profile?->get('last_name')->value ?? '')),
    ];
  }

  private function writeLog(bool $success, string $template_id, string $recipient, string $subject, array $options, string $error): void {
    try {
      $values = [
        'code' => $success ? 10 : 90,
        'message' => $success
          ? sprintf('Email sent: %s', $template_id)
          : sprintf('Email failed: %s', $template_id),
        'context' => json_encode([
          'template' => $template_id,
          'recipient' => $recipient,
          'subject' => $subject,
          'success' => $success,
          'error' => $error,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      ];
      if (!empty($options['related_course'])) {
        $values['related_course'] = (int) $options['related_course'];
      }
      if (!empty($options['related_user'])) {
        $values['related_user'] = (int) $options['related_user'];
      }
      $this->entityTypeManager->getStorage('dhcr_log_entry')->create($values)->save();
    }
    catch (\Throwable) {
      // Logging must never turn a completed mail operation into a failure.
    }
  }

}
