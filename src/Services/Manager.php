<?php

namespace Drupal\content_duplicator\Services;

use Drupal\Core\Controller\ControllerBase;
use Drupal\apivuejs\Services\DuplicateEntityReference;

/**
 *
 * @author stephane
 *        
 */
class Manager extends ControllerBase {
  
  /**
   *
   * @var DuplicateEntityReference
   */
  protected $DuplicateEntityReference;
  
  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *        The entity type manager.
   */
  public function __construct(DuplicateEntityReference $DuplicateEntityReference) {
    $this->DuplicateEntityReference = $DuplicateEntityReference;
  }
  
  /**
   *
   * @param int $site_internet_entity
   * @return \Drupal\creation_site_virtuel\Entity\SiteInternetEntity
   */
  function createClone(int $site_internet_entity, $SiteTypeDatas = null, $duplicate = true) {
    /**
     *
     * @var \Drupal\creation_site_virtuel\Entity\SiteInternetEntity $entity
     */
    $entity = $this->entityTypeManager()->getStorage('site_internet_entity')->load($site_internet_entity);
    if ($entity) {
      if (!$SiteTypeDatas) {
        $values = [
          'site_internet_entity_type' => $entity->bundle()
        ];
        $SiteTypeDatas = \Drupal\creation_site_virtuel\Entity\SiteTypeDatas::create($values);
      }
      
      $SiteTypeDatas->set('name', $entity->getName() . ' clone : ' . $entity->id());
      $SiteTypeDatas->set('name_menu', $entity->getName());
      $SiteTypeDatas->set('page_supplementaires', []);
      $SiteTypeDatas->set('is_home_page', false);
      $SiteTypeDatas->set('layout_paragraphs', $entity->get('layout_paragraphs')->getValue());
      $setValues = [];
      if (\Drupal\lesroidelareno\lesroidelareno::getCurrentDomainId() !== 'wb_horizon_com')
        $setValues = [
          \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD => 'wb_horizon_com',
          \Drupal\domain_source\DomainSourceElementManagerInterface::DOMAIN_SOURCE_FIELD => 'wb_horizon_com'
        ];
      $newEntity = $this->DuplicateEntityReference->duplicateEntity($SiteTypeDatas, false, [], $setValues, $duplicate);
      
      $ids[] = $newEntity->id();
      $entity->set('entities_duplicate', $ids);
      $entity->save();
      return $entity;
    }
    $this->messenger()->addError(" Une erreur s'est produite ");
  }
  
  /**
   *
   * @param int $site_internet_entity
   * @param int $SiteTypeDatas
   */
  function updateClone(int $site_internet_entity, int $site_type_datas) {
    /**
     *
     * @var \Drupal\creation_site_virtuel\Entity\SiteInternetEntity $SiteInternetEntity
     */
    $SiteInternetEntity = $this->entityTypeManager()->getStorage('site_internet_entity')->load($site_internet_entity);
    /**
     *
     * @var \Drupal\creation_site_virtuel\Entity\SiteTypeDatas $SiteTypeDatas
     */
    $SiteTypeDatas = $this->entityTypeManager()->getStorage('site_type_datas')->load($site_type_datas);
    if ($SiteInternetEntity && $SiteTypeDatas) {
      $this->DuplicateEntityReference->deleteSubEntity($SiteTypeDatas);
      return $this->createClone($site_internet_entity, $SiteTypeDatas, false);
    }
    $this->messenger()->addError(" Une erreur s'est produite ");
  }
  
}