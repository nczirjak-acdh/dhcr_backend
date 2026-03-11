<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;

final class DhcrInviteTranslationForm extends DhcrContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $entity = $this->getEntity();
    $is_new = $entity->isNew();
    $title = $is_new ? $this->t('Add Translation') : $this->t('Edit Translation');

    $form['#attached']['library'][] = 'dhcr_backend/admin_translation';
    $form['#attributes']['class'][] = 'dhcr-translation-form';

    $form['dhcr_translation_heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-translation-form__heading"><i class="fas fa-pencil-alt" aria-hidden="true"></i><span>' . $title . '</span></h2>',
    ];

    if (isset($form['sort_order'])) {
      $form['sort_order']['#access'] = FALSE;
    }

    $fieldset = [
      '#type' => 'fieldset',
      '#title' => $title,
      '#attributes' => ['class' => ['dhcr-translation-form__fieldset']],
      '#weight' => 0,
    ];

    if (isset($form['language'])) {
      $fieldset['language'] = $form['language'];
      unset($form['language']);
      if (isset($fieldset['language']['widget']['#title'])) {
        $fieldset['language']['widget']['#title'] = $this->t('Language');
      }
    }

    $fieldset['subject_prefix_note'] = [
      '#type' => 'markup',
      '#markup' => '<p class="dhcr-translation-form__note">' . $this->t('The prefix [DH Course Registry] will be automatically added to the subject:') . '</p>',
      '#weight' => 10,
    ];

    if (isset($form['subject'])) {
      $fieldset['subject'] = $form['subject'];
      unset($form['subject']);
      if (isset($fieldset['subject']['widget'][0]['value'])) {
        $fieldset['subject']['widget'][0]['value']['#title'] = $this->t('Subject');
      }
    }

    $fieldset['message_help'] = [
      '#type' => 'markup',
      '#markup' => '<div class="dhcr-translation-form__help">' .
        '<p>' . $this->t('The following words are required in the message:') . '<br><strong><i><u>-fullname-</u></i></strong><br>' .
        $this->t('This will be automatically replaced by the academic title, first name and last name of the person who is sending the invitation.') . '<br>' .
        '<strong><i><u>-passwordlink-</u></i></strong><br>' .
        $this->t('This will be automatically replaced by a link where the user can set his password. This link is only visible in the email.') . '</p>' .
        '<p>*' . $this->t('Please do not change or translate those words.') . '<br>*' .
        $this->t('Take a look at the English translation as an example of how to use it.') . '<br>*' .
        $this->t('Recommended: Use the text from the message textfield on the edit page, to avoid double signatures.') . '</p>' .
        '</div>',
      '#weight' => 20,
    ];

    if (isset($form['message_body'])) {
      $fieldset['message_body'] = $form['message_body'];
      unset($form['message_body']);
      if (isset($fieldset['message_body']['widget'][0]['value'])) {
        $fieldset['message_body']['widget'][0]['value']['#title'] = $this->t('Message');
        $fieldset['message_body']['widget'][0]['value']['#rows'] = 50;
      }
    }

    if (isset($form['published'])) {
      $fieldset['published'] = $form['published'];
      unset($form['published']);
      if (isset($fieldset['published']['widget']['value'])) {
        $fieldset['published']['widget']['value']['#title'] = $this->t('Publish');
      }
    }

    $form['translation'] = $fieldset;

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $message = (string) $form_state->getValue(['message_body', 0, 'value']);
    foreach (['-fullname-', '-passwordlink-'] as $required_token) {
      if (!str_contains($message, $required_token)) {
        $form_state->setErrorByName('message_body', $this->t('The message must contain the token %token.', ['%token' => $required_token]));
      }
    }

    $language_target_id = $form_state->getValue('language');
    if ($language_target_id) {
      $query = $this->entityTypeManager
        ->getStorage('dhcr_invite_translation')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('language', (int) $language_target_id);

      if (!$this->getEntity()->isNew()) {
        $query->condition('id', (int) $this->getEntity()->id(), '<>');
      }

      if ($query->range(0, 1)->execute()) {
        $form_state->setErrorByName('language', $this->t('A translation for this language already exists.'));
      }
    }
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    if ($entity->isNew() && (int) $entity->get('sort_order')->value === 0) {
      $query = $this->entityTypeManager
        ->getStorage('dhcr_invite_translation')
        ->getQuery()
        ->accessCheck(FALSE)
        ->sort('sort_order', 'DESC')
        ->range(0, 1);
      $ids = $query->execute();
      $next_sort_order = 1;
      if ($ids) {
        $last = $this->entityTypeManager->getStorage('dhcr_invite_translation')->load(reset($ids));
        $next_sort_order = ((int) $last?->get('sort_order')->value) + 1;
      }
      $entity->set('sort_order', $next_sort_order);
    }

    return parent::save($form, $form_state);
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['submit'])) {
      $actions['submit']['#value'] = $this->getEntity()->isNew()
        ? $this->t('Save Translation')
        : $this->t('Update Translation');
      $actions['submit']['#attributes']['class'][] = 'button--dhcr-outline';
    }
    return $actions;
  }

}
