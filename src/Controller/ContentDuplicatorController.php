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
    /**
     * @var \Drupal\creation_site_virtuel\Entity\SiteInternetEntity
     */
    $entity = $this->entityTypeManager->getStorage('site_internet_entity')->load($site_internet_entity);
    // dd([$entity->referencedEntities()[3]->toArray(), $entity->getFieldDefinitions(), $entity->toArray()]);
    $newEntity = $this->duplicateEntity($entity);
    // dd($entity->toArray());
    // $newEntity = $entity->createDuplicate();
    $newEntity->setName($newEntity->getName() . " Clone");
    // $newEntity->enforceIsNew();
    // $newEntity->save();

    $destination = $newEntity->toUrl();

    return $this->redirect($destination->getRouteName(), $destination->getRouteParameters());
  }

  /**
   * @param \Drupal\Core\Entity\EntityBase $entity
   * @param boolean $is_sub
   * @return boolean
   */
  public function duplicateEntity($entity, $is_sub = false) {
    $newEntity = $entity->createDuplicate();
    // dump($class_name);
    // $newEntity->__construct($newEntity->toArray(), $class_name);
    $ref_entities = $entity->referencedEntities();
    dd($ref_entities);
    // dd($ref_entities[0]);
    $refs_values = [];
    // dump($ref_entities);
    // dump($entity->getFieldsDefinitions());
    $new_field_values = [];
    for ($i = 0; $i < count($ref_entities); $i++) {
      // dump([ $ref_entities[$i]->getEntityTypeId(), $ref_entities[$i]->id()]);
      $ent_id = $ref_entities[$i]->getEntityTypeId();
      if ($ent_id != "user") {
        $fields_names =  $this->getFieldKey($entity->toArray(), $ref_entities[$i]->id());
        foreach ($fields_names as $name) {
          $field_entity_id = $entity->get($name)->getFieldDefinition()->getItemDefinition()->toArray()["settings"]["target_type"];
          if ($field_entity_id == $ref_entities[$i]->getEntityTypeId()) {
            $new_id = $this->duplicateEntity($ref_entities[$i], true);
            // $new_id = 3;
            if (isset($refs_values[$name])) {
              $refs_values[$name][] = $new_id;
            } else {
              $refs_values[$name] = [$new_id];
            }
            break;
          }
        }
        // dump($newEntity->get($field_name[0])->getValue());
        // dump([$ref_entities[$i]->getEntityTypeId(), $entity->get($field_name[0])->getFieldDefinition()->getItemDefinition()->toArray()["settings"]["target_type"]]);
      }
    }
    foreach ($refs_values as $key => $value) {
      $newEntity->set($key, $value);
    }

    // dd($refs_values, $newEntity->toArray());
    $newEntity->save();
    return $is_sub ? $newEntity->id() : $newEntity;
  }

  /**
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
          break;
        }
      }
    }
    return $result;
  }
}
