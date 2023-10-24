<?php

namespace Drupal\content_duplicator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use PhpParser\Node\Expr\Instanceof_;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apivuejs\Services\DuplicateEntityReference;

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
   *        The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DuplicateEntityReference $DuplicateEntityReference) {
    $this->entityTypeManager = $entity_type_manager;
    $this->DuplicateEntityReference = $DuplicateEntityReference;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('apivuejs.duplicate_reference'));
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
    /**
     *
     * @var \Drupal\creation_site_virtuel\Entity\SiteInternetEntity $entity
     */
    $entity = $this->entityTypeManager->getStorage('site_internet_entity')->load($site_internet_entity);
    if ($entity) {
      $values = [
        'site_internet_entity_type' => $entity->bundle()
      ];
      $SiteTypeDatas = \Drupal\creation_site_virtuel\Entity\SiteTypeDatas::create($values);
      $SiteTypeDatas->set('name', $entity->getName() . ' clone : ' . $entity->id());
      $SiteTypeDatas->set('name_menu', $entity->getName());
      $SiteTypeDatas->set('page_supplementaires', []);
      $SiteTypeDatas->set('is_home_page', false);
      $SiteTypeDatas->set('layout_paragraphs', $entity->get('layout_paragraphs')->getValue());
      $newEntity = $this->DuplicateEntityReference->duplicateEntity($SiteTypeDatas);
      $destination = $newEntity->toUrl();
      return $this->redirect($destination->getRouteName(), $destination->getRouteParameters());
    }
    $this->messenger()->addError(" Une erreur s'est produite ");
    return [];
  }
  
  /**
   *
   * @param array $valueList
   * @param int $entity_id
   * @return array<string>
   */
  protected function getFieldKey($valueList, $entity_id) {
    $result = [];
    foreach ($valueList as $key => $field) {
      foreach ($field as $field_item) {
        if (isset($field_item["target_id"]) && $entity_id == $field_item["target_id"]) {
          $result[] = $key;
        }
      }
    }
    return $result;
  }
  
}
