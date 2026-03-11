<?php

declare(strict_types=1);

namespace Drupal\dhcr_backend\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class DhcrContentEntityForm extends ContentEntityForm {

  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $this->messenger()->addStatus($this->t('Saved %label.', ['%label' => $entity->label()]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $status;
  }

}
