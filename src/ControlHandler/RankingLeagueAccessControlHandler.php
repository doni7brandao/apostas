<?php

/**
 * @file
 * Contains Drupal\mespronos\RankingLeagueAccessControlHandler.
 */

namespace Drupal\mespronos\ControlHandler;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;

/**
 * Access controller for the RankingLeague entity.
 *
 * @see \Drupal\mespronos\Entity\RankingLeague.
 */
class RankingLeagueAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if (is_null($account)) {
      $account = User::load(\Drupal::currentUser()->id());
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'manage mespronos content');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'manage mespronos content');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage mespronos content');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add RankingLeague entity');
  }

}
