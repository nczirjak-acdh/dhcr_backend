<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;

final class DhcrCityForm extends DhcrContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'dhcr_backend/admin_city';
    $form['#attributes']['class'][] = 'dhcr-city-form';

    $form['dhcr_city_heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-city-form__heading">+ ' . $this->t('Add City') . '</h2>',
    ];

    if (isset($form['name']['widget'][0]['value'])) {
      $form['name']['widget'][0]['value']['#title'] = $this->t('Name');
    }

    if (isset($form['country']['widget'][0]['target_id'])) {
      $form['country']['widget'][0]['target_id']['#type'] = 'select';
      $form['country']['widget'][0]['target_id']['#title'] = $this->t('Country');
      $form['country']['widget'][0]['target_id']['#empty_option'] = $this->t('- Select country -');
    }

    if (isset($form['lat'])) {
      $form['lat']['#access'] = FALSE;
    }
    if (isset($form['lng'])) {
      $form['lng']['#access'] = FALSE;
    }

    return $form;
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['submit'])) {
      $actions['submit']['#value'] = $this->t('Save City');
      $actions['submit']['#attributes']['class'][] = 'button--dhcr-outline';
    }
    return $actions;
  }

}
