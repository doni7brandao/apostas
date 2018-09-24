<?php

/**
 * @file
 * Contains Drupal\mespronos\Entity\Form\LeagueForm.
 */

namespace Drupal\mespronos\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the League entity edit forms.
 *
 * @ingroup mespronos
 */
class LeagueForm extends ContentEntityForm {
  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['creator']['#access'] = false;
    return $form;
  }


  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    if ($status) {
      drupal_set_message($this->t('La compétition "%label" a été enregistrée.', array(
        '%label' => $entity->label(),
      )));
    } else {
      drupal_set_message($this->t('The %label League was not saved.', array(
        '%label' => $entity->label(),
      )));
    }
    $form_state->setRedirect('entity.league.collection');
  }

}
