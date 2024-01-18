<?php

namespace Drupal\content_duplicator\Services;

use Drupal\Core\Controller\ControllerBase;
use Drupal\apivuejs\Services\DuplicateEntityReference;
use Drupal\lesroidelareno\lesroidelareno;

/**
 *
 * @author stephane
 *        
 */
class Manager extends ControllerBase {
  protected $update_header = false;
  protected $update_footer = false;
  
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
  function createClone(int $site_internet_entity, $ModeleDePage = null, $duplicate = true) {
    /**
     * La page à dupliquer.
     *
     * @var \Drupal\creation_site_virtuel\Entity\SiteInternetEntity $entityToDuplicate
     */
    $entityToDuplicate = $this->entityTypeManager()->getStorage('site_internet_entity')->load($site_internet_entity);
    if ($entityToDuplicate) {
      $ids = $entityToDuplicate->getModeleDePagesIds();
      $HomePage = $entityToDuplicate->isHomePage();
      if (!$ModeleDePage) {
        $values = [
          'site_internet_entity_type' => $entityToDuplicate->bundle()
        ];
        $ModeleDePage = \Drupal\creation_site_virtuel\Entity\SiteTypeDatas::create($values);
        $ModeleDePage->set('page_supplementaires', []);
        $ModeleDePage->set('is_home_page', $HomePage);
        // si cest la premiere generation d'une page d'accueil, on ajoute
        // egalement l'entete et le pied de page.
        if ($HomePage) {
          if ($id_header = $this->getParagraphHeaderFooter('top_header'))
            $ModeleDePage->set('entete_paragraph', $id_header);
          if ($id_footer = $this->getParagraphHeaderFooter('footer'))
            $ModeleDePage->set('footer_paragraph', $id_footer);
        }
      }
      if ($HomePage) {
        if ($id_header = $this->getParagraphHeaderFooter('top_header') && $this->update_header)
          $ModeleDePage->set('entete_paragraph', $id_header);
        if ($id_footer = $this->getParagraphHeaderFooter('footer') && $this->update_footer)
          $ModeleDePage->set('footer_paragraph', $id_footer);
      }
      $ModeleDePage->set('name', $entityToDuplicate->getName() . ' clone : ' . $entityToDuplicate->id());
      $ModeleDePage->set('name_menu', $entityToDuplicate->getName());
      $ModeleDePage->set('layout_paragraphs', $entityToDuplicate->get('layout_paragraphs')->getValue());
      $setValues = [];
      if (\Drupal\lesroidelareno\lesroidelareno::getCurrentDomainId() !== 'wb_horizon_com')
        $setValues = [
          \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD => 'wb_horizon_com',
          \Drupal\domain_source\DomainSourceElementManagerInterface::DOMAIN_SOURCE_FIELD => 'wb_horizon_com'
        ];
      $newEntity = $this->DuplicateEntityReference->duplicateEntity($ModeleDePage, false, [], $setValues, $duplicate);
      if (!in_array($newEntity->id(), $ids)) {
        $ids[] = $newEntity->id();
        $entityToDuplicate->set('entities_duplicate', $ids);
        $entityToDuplicate->save();
      }
      return $ModeleDePage;
    }
    $this->messenger()->addError(" Une erreur s'est produite ");
  }
  
  /**
   * Permet de recuperer l'entete ou le footer du site.
   */
  protected function getParagraphHeaderFooter(string $region) {
    $query = $this->entityTypeManager()->getStorage('block')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('theme', lesroidelareno::getCurrentDomainId());
    $query->condition('region', $region);
    $query->condition('provider', 'entity_block');
    $query->condition('plugin', 'entity_block:paragraph');
    $query->condition('status', true);
    $ids = $query->execute();
    if ($ids) {
      $block = \Drupal\block\Entity\Block::load(reset($ids));
      if ($block) {
        $settings = $block->get('settings');
        return !empty($settings['entity']) ? $settings['entity'] : NULL;
      }
    }
    return NULL;
  }
  
  /**
   *
   * @param int $site_internet_entity
   * @param int $SiteTypeDatas
   */
  function updateClone(int $site_internet_entity, int $site_type_datas, bool $update_header = false, $update_footer = false) {
    $this->update_footer = $update_footer;
    $this->update_header = $update_header;
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