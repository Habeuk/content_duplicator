<?php

namespace Drupal\content_duplicator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use PhpParser\Node\Expr\Instanceof_;
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



  protected $duplicable_entities_types = [
    "paragraph",
    "blocks_contents",
    "block_content",
    "node",
    "commerce_product",
    "webform"
  ];


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
    dump($newEntity->toArray());
    // $newEntity = $entity->createDuplicate();
    // $newEntity->setName($newEntity->getName() . " Clone");
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
  public function duplicateEntity($entity,  $is_sub = false, $fieldsList = []) {
    $newEntity = $entity->createDuplicate();
    $arrayValue = count($fieldsList) ? $fieldsList : $newEntity->toArray();
    $updatedValue = [];
    // dd($entity->referencedEntities()[3]);
    // dd($entity->referencedEntities()[3]->referencedEntities()[3]->getEntityType());
    // dump($arrayValue);
    foreach ($arrayValue as $field => $value) {
      if ($entity instanceof \Drupal\webform\Entity\Webform) {
        $newEntity->set("id", substr($entity->id(), 0, 10) . date('YMdi') . rand(0, 9999));
        $newEntity->save();
        break;
      }
      if (array_key_exists($field, $arrayValue)) {
        // dd($arrayValue);
        if (gettype($entity->get($field)) != "object") {
          // $newEntity->save();

          dd($arrayValue, $entity instanceof \Drupal\webform\Entity\Webform, $newEntity);
          break;
        }
        // dump([$field, $entity->get($field)]);
        $entity_type_id = $entity->get($field)->getFieldDefinition()->getItemDefinition()->toArray()["settings"]["target_type"];
        if (isset($value[0]["target_id"]) && in_array($entity_type_id, $this->duplicable_entities_types)) {
          $valueList = [];
          foreach ($value as  $entity_id) {
            $sub_entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id['target_id']);
            if (isset($sub_entity)) {
              $valueList[] = $this->duplicateEntity($sub_entity, true);
              // $valueList[] = 7;
            } else {
              dd("error");
            }
            # code...
          }
          $newEntity->set($field, $valueList);
        }
      }
      $newEntity->save();
      // dd($newEntity);
    }
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
        }
      }
    }
    return $result;
  }
}
