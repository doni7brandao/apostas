<?php

namespace Drupal\mespronos\Entity\Getters;

use Drupal\mespronos\Entity\Game;
use Drupal\mespronos\Entity\League;
use Drupal\mespronos\Entity\Team;

trait DayGettersTrait {

  public function label() {
    return $this->get('name')->value;
  }

  public function getRenderableLabel() {
    $league = $this->getLeague();
    return [
      '#theme' => 'day-small',
      '#league' => [
        'label' => t($league->label()),
        'logo' => $league->getLogo('mini_logo'),
        'url' => $league->url(),
      ],
      '#day'=> [
        'url'=> $this->url(),
        'label'=> t($this->label()),
      ]
    ];
  }

  /**
   * @return \Drupal\mespronos\Entity\League
   */
  public function getLeague() : League {
    return League::load($this->get('league')->target_id);
  }

  /**
   * @return integer
   */
  public function getLeagueID() : int {
    return $this->get('league')->target_id;
  }

  /**
   * Return the number of games of the day
   *
   * @return int
   *   Number of games for the day
   */
  public function getNbGame() : int {
    $query = \Drupal::entityQuery('game')->condition('day', $this->id());
    $ids = $query->execute();
    return count($ids);
  }

  /**
   * Return all games for day
   *
   * @return \Drupal\mespronos\Entity\Game[]
   */
  public function getGames($onlyFuture = FALSE) {
    $ids = $this->getGamesId($onlyFuture);
    return Game::loadMultiple($ids);
  }

  /**
   * Return all games id for day
   * @return integer[]
   */
  public function getGamesId($onlyFuture = FALSE) {
    $query = \Drupal::entityQuery('game');
    $query->condition('day', $this->id());
    if($onlyFuture) {
      $now = new \DateTime(NULL, new \DateTimeZone('UTC'));
      $query->condition('game_date', $now->format('Y-m-d\TH:i:s'), '>');
    }
    $query->sort('game_date');
    $query->sort('id');

    return $query->execute();
  }

  /**
   * Return the number of games of the day with score setted
   *
   * @return int
   *   Number of games with score setted
   */
  public function getNbGameWIthScore() {
    $query = \Drupal::entityQuery('game')
      ->condition('day', $this->id())
      ->condition('score_team_1', NULL, 'IS NOT')
      ->condition('score_team_2', NULL, 'IS NOT');
    $ids = $query->execute();
    return \count($ids);
  }

}