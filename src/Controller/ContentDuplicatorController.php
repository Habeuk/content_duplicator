<?php

namespace Drupal\content_duplicator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Content duplicator routes.
 */
class ContentDuplicatorController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the response.
   */
  public function build($node) {
    $entity = $this->entityTypeManager->getStorage('node')->load($node);
    
    $newEntity = $entity->createDuplicate(); 
    $newEntity->setTitle($newEntity->getTitle() . " Clone");
    $newEntity->save();

    $destination = $newEntity->toUrl();

    return $this->redirect($destination->getRouteName(), $destination->getRouteParameters());
  }

  /**
   * Builds the response.
   */
  public function buildSite($site_internet_entity) {
    $entity = $this->entityTypeManager->getStorage('site_internet_entity')->load($site_internet_entity);
    
    $newEntity = $entity->createDuplicate(); 
    $newEntity->setName($newEntity->getName() . " Clone");
    $newEntity->save();

    $destination = $newEntity->toUrl();

    return $this->redirect($destination->getRouteName(), $destination->getRouteParameters());
  }

}
