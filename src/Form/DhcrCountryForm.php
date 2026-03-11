<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;

final class DhcrCountryForm extends DhcrContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'dhcr_backend/admin_country';
    $form['#attributes']['class'][] = 'dhcr-country-form';

    $form['dhcr_country_heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-country-form__heading">+ ' . $this->t('Add Country') . '</h2>',
    ];

    if (isset($form['name']['widget'][0]['value'])) {
      $form['name']['widget'][0]['value']['#title'] = $this->t('Name');
    }

    if (isset($form['iso2'])) {
      $form['iso2']['#access'] = FALSE;
    }

    return $form;
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['submit'])) {
      $actions['submit']['#value'] = $this->t('Save Country');
      $actions['submit']['#attributes']['class'][] = 'button--dhcr-outline';
    }
    return $actions;
  }

}
