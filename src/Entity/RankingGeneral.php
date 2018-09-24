<?php

/**
 * @file
 * Contains Drupal\mespronos\Entity\RankingGeneral.
 */

namespace Drupal\mespronos\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mespronos\Controller\RankingController;
use Drupal\Core\Database\Database;
use Drupal\mespronos\Entity\Base\RankingBase;
use Drupal\mespronos_group\Entity\Group;

/**
 * Defines the RankingGeneral entity.
 *
 * @ingroup mespronos
 *
 * @ContentEntityType(
 *   id = "ranking_general",
 *   label = @Translation("RankingGeneral entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\mespronos\Entity\Controller\RankingGeneralListController",
 *     "views_data" = "Drupal\mespronos\Entity\ViewsData\RankingGeneralViewsData",
 *     "access" = "Drupal\mespronos\ControlHandler\RankingGeneralAccessControlHandler",
 *   },
 *   base_table = "mespronos__ranking_general",
 *   admin_permission = "administer RankingGeneral entity",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class RankingGeneral extends RankingBase {

  public function getBaseTable() {
    return 'mespronos__ranking_general';
  }

  public function getEntityRelated() {
    return 'general';
  }

  public function getStorageName() {
    return 'ranking_general';
  }
  
  public static function createRanking() {
    self::removeRanking();
    $data = self::getData();
    RankingController::sortRankingDataAndDefinedPosition($data);
    foreach ($data as $row) {
      $rankingLeague = self::create([
        'better' => $row->better,
        'games_betted' => $row->nb_bet,
        'points' => $row->points,
      ]);
      $rankingLeague->save();
    }
    return count($data);
  }

  public static function getData() {
    $injected_database = Database::getConnection();
    $query = $injected_database->select('mespronos__ranking_league', 'rl');
    $query->addField('rl', 'better');
    $query->addExpression('sum(rl.points)', 'points');
    $query->addExpression('sum(rl.games_betted)', 'nb_bet');
    $query->join('mespronos__league', 'l', 'l.id = rl.league');
    $query->groupBy('rl.better');
    $query->orderBy('points', 'DESC');
    $query->orderBy('nb_bet', 'DESC');
    $query->condition('l.status', array('active', 'over'), 'IN');
    $results = $query->execute()->fetchAllAssoc('better');

    return $results;
  }

  public static function removeRanking() {
    $storage = \Drupal::entityTypeManager()->getStorage('ranking_general');
    $query = \Drupal::entityQuery('ranking_general');
    $ids = $query->execute();

    $rankings = $storage->loadMultiple($ids);
    $nb_deleted = count($rankings);
    foreach ($rankings as $ranking) {
      $ranking->delete();
    }
    return $nb_deleted;
  }

  public function getPosition() {
    if(\Drupal::moduleHandler()->moduleExists('domain')) {
      $results = db_query('SELECT count(*) +1 as position from {mespronos__ranking_general} rg join {users_field_data} ufd  on ufd.uid = rg.better and ufd.status = 1 and ufd.bet_private = 0 WHERE points > :points', [
        ':points'=> $this->getPoints(),
      ]);
    }
    else {
      $results = db_query('SELECT count(*) +1 as position from {mespronos__ranking_general} rg join {users_field_data} ufd  on ufd.uid = rg.better and ufd.status = 1 WHERE points > :points', [
        ':points'=> $this->getPoints(),
      ]);
    }

    $res = $results->fetchField();
    if ($res) {
      return (int) $res;
    }
    return FALSE;
  }

  /**
   * @return \Drupal\mespronos\Entity\RankingGeneral
   */
  public static function getRanking($entity = null, $entity_name = 'general', $storage_name = 'ranking_general', Group $group = null) {
    return parent::getRanking(null, $entity_name, $storage_name, $group);
  }

  /**
   * @return \Drupal\mespronos\Entity\RankingGeneral
   */
  public static function getRankingAverage($entity = null, $entity_name = 'general', $storage_name = 'ranking_general', Group $group = null) {
    return parent::getRankingAverage(null, $entity_name, $storage_name, $group);
  }

  /**
   * Get General ranking for user
   * @param \Drupal\user\Entity\User $better
   * @param \Drupal\mespronos\Entity\Base\RankingBase $entity
   * @param string $entity_name
   * @param string $storage_name
   * @return \Drupal\mespronos\Entity\RankingGeneral
   */
  public static function getRankingForBetter(\Drupal\user\Entity\User $better, $entity = null, $entity_name = null, $storage_name = null) {
    return parent::getRankingForBetter($better, null, null, 'ranking_general');
  }

  public static function getNumberOfBetters($entity = NULL, $entity_name = NULL, $storage_name = NULL, Group $group = NULL) {
    return parent::getNumberOfBetters(NULL, NULL, 'ranking_general', $group);
  }

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    return $fields;
  }

}
