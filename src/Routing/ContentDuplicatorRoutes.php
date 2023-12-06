<?php

namespace Drupal\content_duplicator\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 */
class ContentDuplicatorRoutes {
  
  /**
   *
   * {@inheritdoc}
   */
  public function routes() {
    $route_collection = new RouteCollection();
    $route = new Route(
        // Path to attach this route to:
        '/content-duplicator/{site_internet_entity}/duplicate-site', 
        // Route defaults:
        [
          '_controller' => '\Drupal\content_duplicator\Controller\ContentDuplicatorController::buildSite',
          '_title' => 'Genere le model de page'
        ], 
        // Route requirements:
        [
          '_role' => 'administrator'
        ], 
        // options
        [
          '_admin_route' => true
        ]);
    $route_collection->add('content_duplicator.duplicate.site', $route);
    return $route_collection;
  }
  
}
