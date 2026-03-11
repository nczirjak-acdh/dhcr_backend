<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dhcr_backend\Utility\DhcrMapConfig;

final class DhcrInstitutionForm extends DhcrContentEntityForm {

  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $entity = $this->getEntity();
    $lon = (float) ($entity->get('lon')->value ?? 16.377208);
    $lat = (float) ($entity->get('lat')->value ?? 48.209131);

    $form['#attached']['library'][] = 'dhcr_backend/admin_institution';
    $form['#attributes']['class'][] = 'dhcr-institution-form';

    $form['dhcr_institution_breadcrumb'] = [
      '#type' => 'markup',
      '#weight' => -110,
      '#markup' => '<div class="dhcr-institution-form__crumbs"><a href="/admin/content/dhcr">' . $this->t('Dashboard') . '</a> / <a href="/admin/content/dhcr/category-lists">' . $this->t('Category Lists') . '</a> / <a href="/admin/content/dhcr/institutions">' . $this->t('Institutions') . '</a> / <span>' . $this->t('Add Institution') . '</span></div>',
    ];

    $form['dhcr_institution_heading'] = [
      '#type' => 'markup',
      '#weight' => -100,
      '#markup' => '<h2 class="dhcr-institution-form__heading">+ ' . $this->t('Add Institution') . '</h2>',
    ];

    if (isset($form['name']['widget'][0]['value'])) {
      $form['name']['widget'][0]['value']['#title'] = $this->t('Name');
    }

    if (isset($form['city']['widget'][0]['target_id'])) {
      $form['city']['widget'][0]['target_id']['#type'] = 'select';
      $form['city']['widget'][0]['target_id']['#title'] = $this->t('City');
      $form['city']['widget'][0]['target_id']['#empty_option'] = $this->t('- Select city -');
    }

    if (isset($form['country']['widget'][0]['target_id'])) {
      $form['country']['widget'][0]['target_id']['#type'] = 'select';
      $form['country']['widget'][0]['target_id']['#title'] = $this->t('Country');
      $form['country']['widget'][0]['target_id']['#empty_option'] = $this->t('- Select country -');
    }

    if (isset($form['description']['widget'][0]['value'])) {
      $form['description']['widget'][0]['value']['#title'] = $this->t('Description');
    }

    if (isset($form['website']['widget'][0]['uri'])) {
      $form['website']['widget'][0]['uri']['#title'] = $this->t('Url');
    }

    if (isset($form['lon']['widget'][0]['value'])) {
      $form['lon']['widget'][0]['value']['#type'] = 'hidden';
    }
    if (isset($form['lat']['widget'][0]['value'])) {
      $form['lat']['widget'][0]['value']['#type'] = 'hidden';
    }

    $form['dhcr_location_help'] = [
      '#type' => 'markup',
      '#weight' => 50,
      '#markup' => '<div class="dhcr-institution-form__location-help"><strong>' . $this->t('Location') . '</strong><br>'
        . $this->t('Select the location on the map below.') . '<br>'
        . $this->t('-You can zoom using the scroll wheel.') . '<br>'
        . $this->t('-You can move the map, by dragging with the mouse.') . '<br>'
        . $this->t('-Place the blue marker on the correct position, it will be saved automatically.')
        . '</div>',
    ];

    $form['dhcr_map'] = [
      '#type' => 'container',
      '#weight' => 51,
      '#attributes' => ['id' => 'dhcr-institution-map', 'style' => 'width: 660px; height: 560px;'],
      '#attached' => [
        'library' => ['dhcr_backend/course_form', 'dhcr_backend/institution_form_map'],
        'drupalSettings' => [
          'dhcrInstitution' => [
            'mapboxToken' => DhcrMapConfig::getMapboxToken(),
            'initialLon' => $lon,
            'initialLat' => $lat,
          ],
        ],
      ],
    ];

    return $form;
  }

  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['submit'])) {
      $actions['submit']['#value'] = $this->t('Save Institution');
      $actions['submit']['#attributes']['class'][] = 'button--dhcr-outline';
    }
    return $actions;
  }

}
