<?php

namespace Drupal\mespronos\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form when deleting mespronos datas
 */
class RemoveDataForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete all data from MesPronos?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mespronos.dashboard');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'confirm';
  }

  public function getFormId() {
    return 'RemoveDataForm';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entities_types = array('ranking_day', 'ranking_league', 'bet', 'game', 'day', 'league', 'team', 'sport');

    foreach ($entities_types as $entity_type) {
      $query = \Drupal::entityQuery($entity_type);
      $ids = $query->execute();
      $controller = \Drupal::entityTypeManager()->getStorage($entity_type);
      $entities = $controller->loadMultiple($ids);
      $controller->delete($entities);
    }

    drupal_set_message('All MesPronos related datas has been removed');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
