<?php
namespace Drupal\mespronos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mespronos\Entity\League;
use Drupal\mespronos\Entity\Base\RankingBase;
use Drupal\mespronos\Entity\RankingDay;
use Drupal\mespronos\Entity\RankingLeague;
use Drupal\mespronos\Entity\RankingGeneral;
use Drupal\mespronos\Entity\Day;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\mespronos_group\Entity\Group;

/**
 * Class DefaultController.
 *
 * @package Drupal\mespronos\Controller
 */
class RankingController extends ControllerBase {

  /**
   * @param \Drupal\mespronos\Entity\Day $day
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public static function recalculateDay(Day $day) {
    $nb_updates = RankingDay::createRanking($day);
    RankingLeague::createRanking($day->getLeague());
    RankingGeneral::createRanking();
    drupal_set_message(t('Ranking updated for @nb betters', array('@nb' => $nb_updates)));
    Cache::invalidateTags(array('ranking'));
    return new RedirectResponse(\Drupal::url('entity.day.collection'));
  }

  public static function sortRankingDataAndDefinedPosition(&$data) {
    usort($data, function ($item1, $item2) {
      if (intval($item1->points) == intval($item2->points)) return 0;
      return intval($item1->points) > intval($item2->points) ? -1 : 1;
    });
    $next_position = 1;
    foreach ($data as &$value) {
      if (isset($old_object) && $old_object->points == $value->points) {
        $value->position = $old_object->position;
      }
      else {
        $value->position = $next_position;
      }
      $next_position++;
      $old_object = $value;
    }
    return $data;

  }

  public static function rankingPage() {
    if (!$ranking = self::getRankingGeneral()) {
      return ['#markup' => t('No ranking for now')];
    }
    return $ranking;
  }

  public static function getRankingGeneral(Group $group = NULL) {
    if($group === NULL && \Drupal::service('mespronos.domain_manager')->getGroupFromDomain()) {
      $group = \Drupal::service('mespronos.domain_manager')->getGroupFromDomain();
    }
    $ranking = RankingGeneral::getRanking(NULL, 'general', 'ranking_general', $group);
    $rankingAverage = RankingGeneral::getRankingAverage(NULL, 'general', 'ranking_general', $group);
    if (\count($ranking) === 0 && \count($rankingAverage) === 0) {
      return FALSE;
    }
    return [
      '#theme' => 'ranking',
      '#general' => self::getTableFromRanking($ranking),
      '#average' => self::getTableFromRanking($rankingAverage),
    ];

  }

  public static function getRankingLeague(League $league, Group $group = NULL) {
    if($group === NULL && \Drupal::service('mespronos.domain_manager')->getGroupFromDomain()) {
      $group = \Drupal::service('mespronos.domain_manager')->getGroupFromDomain();
    }
    $ranking = RankingLeague::getRanking($league, 'league', 'ranking_league', $group);
    if (\count($ranking) === 0) {
      return FALSE;
    }
    return self::getTableFromRanking($ranking);
  }

  public static function getRankingTableForDay(Day $day, Group $group = NULL) {
    if($group === NULL && \Drupal::service('mespronos.domain_manager')->getGroupFromDomain()) {
      $group = \Drupal::service('mespronos.domain_manager')->getGroupFromDomain();
    }
    $rankingDays = RankingDay::getRankingForDay($day, $group);
    if (\count($rankingDays) === 0) {
      return FALSE;
    }
    return self::getTableFromRanking($rankingDays);
  }

  /**
   * @param RankingBase[] $rankings
   * @return array
   */
  public static function getTableFromRanking($rankings) {
    $user = \Drupal::currentUser();
    $rows = [];
    $old_points = NULL;
    $next_rank = 0;
    $current_rank = 0;
    foreach ($rankings as $ranking) {
      $next_rank++;
      if($ranking->get('points')->value != $old_points) {
        $current_rank = $next_rank;
      }

      $better = \Drupal\user\Entity\User::load($ranking->getOwner()->id());
      $better_renderable = UserController::getRenderableUser($better);
      $clean_username = \Drupal::service('kgaut_tools.stringcleaner')->clean($better->getUsername());

      $position = [
        '#markup' => $ranking->get('points')->value != $old_points ? $current_rank : '↪ ' . $current_rank ,
      ];
      $row = [
        'data' => [
          'position' => render($position),
          'better' => [
            'data' => render($better_renderable),
            'class' => ['better-cell'],
          ],
          'points' => $ranking->get('points')->value,
          'games_betted' => $ranking->get('games_betted')->value,
          'average' => round($ranking->get('points')->value / $ranking->get('games_betted')->value, 3),
        ],
        'class' => ['ranking-for-' . $clean_username, 'user-' . $clean_username]
      ];
      $old_points = $ranking->get('points')->value;
      if ((int) $ranking->getOwner()->id() === (int) $user->id()) {
        $row['class'][] = 'highlighted';
        $row['class'][] = 'bold';
      }
      if ($ranking instanceof RankingDay) {
        $link_details_user = Url::fromRoute('mespronos.lastbetsdetailsforuser', ['day' => $ranking->getDayiD(), 'user' => $ranking->getOwner()->id()])->toString();
        $cell = ['#markup' => '<a class="picto" href="' . $link_details_user . '" title="' . t('see user\'s bets') . '"><i class="fa fa-list" aria-hidden="true"></i></a>'];
        $row['data']['details'] = ['data' => render($cell), 'class' => 'picto'];
      }
      $rows[] = $row;
    }
    $header = [
      t('#', [], ['context' => 'mespronos-ranking']),
      t('', [], ['context' => 'mespronos-ranking']),
      t('Points', [], ['context' => 'mespronos-ranking']),
      t('Bets', [], ['context' => 'mespronos-ranking']),
      t('Moyenne', [], ['context' => 'mespronos-ranking']),
    ];

    if (isset($ranking) && $ranking instanceof RankingDay) {
      $header[] = '';
    }
    return [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];
  }

  /**
   * @param \Drupal\user\Entity\User $user
   * @return array
   */
  public static function getPalmares(\Drupal\user\Entity\User $user) {
    $data = self::getPalmaresData($user);
    if (!empty($data)) {
      return [
        '#theme' => 'table',
        '#rows' => self::parsePalmares($data),
        '#header' => self::getPalmaresHeader(),
        '#footer' => self::getPalmaresFooter(),
        '#cache' => [
          'contexts' => ['route'],
          'tags' => ['palmares', 'user:'.$user->id()],
        ],
      ];
    } else {
      return false;
    }
  }

  private static function getPalmaresData(\Drupal\user\Entity\User $user) {

    $injected_database = Database::getConnection();
    $query = $injected_database->select('mespronos__league', 'l');
    $query->join('mespronos__ranking_league', 'rl', 'l.id = rl.league');
    $query->addField('l', 'id', 'league_id');
    $query->orderBy('l.changed', 'DESC');
    $query->condition('l.status', 'archived');
    $query->condition('rl.better', $user->id());
    $palmares = [];
    $results = $query->execute();
    while ($row = $results->fetchObject()) {
      $row->league = League::load($row->league_id);
      $ranking = RankingLeague::getRankingForBetter($user, $row->league);
      $row->betters = $row->league->getBettersNumber();
      $row->position = $ranking ? $ranking->getPosition() : ' ';
      $palmares[] = $row;
    }
    return $palmares;
  }

  public static function getPalmaresHeader() {
    return [
      t('League', array(), array('context' => 'mespronos-block')),
      t('Ranking', array(), array('context' => 'mespronos-block')),
      t('Betters', array(), array('context' => 'mespronos-block')),
    ];
  }

  public static function parsePalmares($data) {
    $rows = [];
    foreach ($data  as $palmares_line) {
      $league_renderable = $palmares_line->league->getRenderableLabel();
      $row = [
        'data' => [
          'league' => [
            'data' => render($league_renderable),
            'class' => ['day-cell']
          ],
          'ranking' => $palmares_line->position,
          'betters' => $palmares_line->betters,
        ]
      ];
      $rows[] = $row;
    }
    return $rows;
  }

  public static function getPalmaresFooter() {
    return [];
  }
}