<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;

final class DhcrLanguageForm extends DhcrContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'dhcr_backend/admin_language';
    $form['#attributes']['class'][] = 'dhcr-language-form';

    $form['dhcr_language_heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-language-form__heading">+ ' . $this->t('Add Language') . '</h2>',
    ];

    if (isset($form['name']['widget'][0]['value'])) {
      $form['name']['widget'][0]['value']['#title'] = $this->t('Name');
    }

    if (isset($form['iso'])) {
      $form['iso']['#access'] = FALSE;
    }

    return $form;
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['submit'])) {
      $actions['submit']['#value'] = $this->t('Save Language');
      $actions['submit']['#attributes']['class'][] = 'button--dhcr-outline';
    }
    return $actions;
  }

}
