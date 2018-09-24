<?php

namespace Drupal\mespronos\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Breadcrumb\Breadcrumb;

/**
 * Class BetBreadcrumbBuilder.
 *
 * @package Drupal\mespronos\Breadcrumb
 */
class BetBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   *
   * @inheritdoc
   */
  public function applies(RouteMatchInterface $route_match) {
    return in_array($route_match->getCurrentRouteMatch()->getRouteName(),
      ['mespronos.day.bet', 'mespronos.lastbetsdetailsforuser']);
  }

  /**
   *
   * @inheritdoc
   */
  public function build(RouteMatchInterface $route_match) {
    /** @var \Drupal\mespronos\Entity\Day $day */
    $day = $route_match->getParameter('day');
    $league = $day->getLeague();
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);
    $links = [];
    $links[] = Link::createFromRoute(t('Home'), '<front>');
    $links[] = Link::createFromRoute(t('Leagues'), 'mespronos.leagues.list');
    $links[] = Link::createFromRoute($league->label(), 'entity.league.canonical', ['league' => $league->id()]);
    return $breadcrumb->setLinks($links);
  }

}