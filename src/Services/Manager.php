<?php

namespace Drupal\content_duplicator\Services;

use Drupal\Core\Controller\ControllerBase;
use Drupal\apivuejs\Services\DuplicateEntityReference;
use Drupal\lesroidelareno\lesroidelareno;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityBase;

/**
 *
 * @author stephane
 *        
 */
class Manager extends ControllerBase {
  protected $update_header = false;
  protected $update_footer = false;
  /**
   * --
   */
  protected $field_domain_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
  /**
   * --
   */
  protected $field_domain_sources = \Drupal\domain_source\DomainSourceElementManagerInterface::DOMAIN_SOURCE_FIELD;
  
  /**
   *
   * @var DuplicateEntityReference
   */
  protected $DuplicateEntityReference;
  /**
   * Le domaine de base.
   *
   * @var string
   */
  protected const domain_base = "wb_horizon_com";
  
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
   * retorune le model de page qui a été dupliqué.
   *
   * @param int $site_internet_entity
   * @return \Drupal\creation_site_virtuel\Entity\SiteTypeDatas
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
        if ($this->update_header && $id_header = $this->getParagraphHeaderFooter('top_header')) {
          $ModeleDePage->set('entete_paragraph', $id_header);
        }
        if ($this->update_footer && $id_footer = $this->getParagraphHeaderFooter('footer')) {
          $ModeleDePage->set('footer_paragraph', $id_footer);
        }
      }
      $ModeleDePage->set('name', $entityToDuplicate->getName() . ' clone : ' . $entityToDuplicate->id());
      $ModeleDePage->set('name_menu', $entityToDuplicate->getName());
      $ModeleDePage->set('layout_paragraphs', $entityToDuplicate->get('layout_paragraphs')->getValue());
      $setValues = [];
      if (\Drupal\lesroidelareno\lesroidelareno::getCurrentDomainId() !== self::domain_base)
        $setValues = [
          \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD => self::domain_base,
          \Drupal\domain_source\DomainSourceElementManagerInterface::DOMAIN_SOURCE_FIELD => self::domain_base
        ];
      $newEntity = $this->DuplicateEntityReference->duplicateEntity($ModeleDePage, false, [], $setValues, $duplicate);
      if (!in_array($newEntity->id(), $ids)) {
        $ids[] = $newEntity->id();
        $entityToDuplicate->set('entities_duplicate', $ids);
        $entityToDuplicate->save();
      }
      return $newEntity;
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
   * Retourne le modele de page.
   *
   * @param int $site_internet_entity
   * @param int $SiteTypeDatas
   * @return \Drupal\creation_site_virtuel\Entity\SiteTypeDatas
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
      // avant de supprimer, il faut verifier que toutes les sous données
      // peuvent etre effectivement supprimées. ( car en cas de bug cest une
      // grosse perte ).
      try {
        $this->VerificationAvantSuppression($SiteTypeDatas);
        $this->DuplicateEntityReference->deleteSubEntity($SiteTypeDatas);
        return $this->createClone($site_internet_entity, $SiteTypeDatas, false);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
      }
      // dd($SiteInternetEntity, $SiteTypeDatas);
    }
    $this->messenger()->addError(" Une erreur s'est produite ...");
    return false;
  }
  
  /**
   * On doit pouvoir supprimer (dans ce cas de figure uniquement) les entities
   * qui porte le domaine wb_horizon_com, car cela garantie que l'entité a été
   * dupliqué et que l'on supprime uniquement les duplications.
   * Les contenus à supprimer doivent avoir un domaine.
   *
   * @param EntityInterface $entity
   * @param array $fieldsList
   */
  public function VerificationAvantSuppression(EntityInterface &$entity, array $fieldsList = []) {
    $EntityTypeId = $entity->getEntityTypeId();
    if ($EntityTypeId == 'webform') {
      $entity_domain_access = $entity->getThirdPartySetting('webform_domain_access', $this->field_domain_access);
      if ($entity_domain_access !== self::domain_base) {
        throw new \Exception("Le domaine de l'entité $EntityTypeId : " . $entity->id() . " est different: " . $entity_domain_access . ' !== ' . self::domain_base);
      }
    }
    elseif ($entity instanceof ContentEntityBase) {
      $arrayValue = $fieldsList ? $fieldsList : $entity->toArray();
      // si l'entité ne fait pas partie des elements à dupliqué, on l'ignore et
      // on regarde s'il a des entites enfants.
      // ca cas se produit sur l'entité de base.
      if (in_array($EntityTypeId, $this->DuplicateEntityReference->getDuplicableEntitiesTypes())) {
        $entity_domain_access = $entity->get($this->field_domain_access)->target_id;
        if ($entity_domain_access !== self::domain_base) {
          throw new \Exception("Le domaine de l'entité $EntityTypeId : " . $entity->id() . " est different: " . $entity_domain_access . ' !== ' . self::domain_base);
        }
      }
      foreach ($arrayValue as $field_name => $value) {
        $settings = $entity->get($field_name)->getSettings();
        // Pour les entites enfants, on s'assure egalement qu'il peuvent etre
        // supprimer.
        // Ainsi seul les entitées authorisées à etre supprimer sont verifier,
        // les autres on les conserve. Par example l'entite user.
        if (!empty($settings['target_type']) && in_array($settings['target_type'], $this->DuplicateEntityReference->getDuplicableEntitiesTypes())) {
          foreach ($value as $entity_id) {
            $sub_entity = $this->entityTypeManager()->getStorage($settings['target_type'])->load($entity_id['target_id']);
            if (!empty($sub_entity)) {
              $this->VerificationAvantSuppression($sub_entity);
            }
          }
        }
      }
    }
  }
  
}