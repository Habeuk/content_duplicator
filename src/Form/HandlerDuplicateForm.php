<?php

namespace Drupal\content_duplicator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_duplicator\Services\Manager;
use Drupal\Core\Url;

/**
 * Provides a Content duplicator form.
 */
class HandlerDuplicateForm extends FormBase {
  
  /**
   *
   * @var Manager
   */
  protected $managerDuplicate;
  
  function __construct(Manager $managerDuplicate) {
    $this->managerDuplicate = $managerDuplicate;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('content_duplicator.manager'));
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_duplicator_handler_duplicate';
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $buidlInfo = $form_state->getBuildInfo()['args'][0];
    /**
     *
     * @var \Drupal\creation_site_virtuel\Entity\SiteInternetEntity $site_internet_entity
     */
    $site_internet_entity = $buidlInfo['site_internet_entity'];
    $ids = $buidlInfo['ids'];
    $form_state->set('site_internet_entity', $buidlInfo['site_internet_entity']);
    $form_state->set('ids', $ids);
    $form['#title'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Duplication : ',
      [
        '#type' => 'html_tag',
        '#tag' => 'i',
        '#value' => $site_internet_entity->getName()
      ]
    ];
    $form['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => "Cette page dispose deja d'un ou plusieurs clone "
    ];
    $form['add_clone'] = [
      '#type' => 'checkbox',
      '#title' => 'Ajouter un nouveau clone',
      '#default_value' => false
    ];
    $options = [];
    foreach ($ids as $id) {
      $SiteTypeDatas = \Drupal\creation_site_virtuel\Entity\SiteTypeDatas::load($id);
      if ($SiteTypeDatas) {
        $link = [
          '#type' => 'link',
          '#title' => $SiteTypeDatas->id(),
          '#url' => Url::fromRoute("entity.site_type_datas.canonical", [
            'site_type_datas' => $SiteTypeDatas->id()
          ]),
          '#options' => [
            'attributes' => [
              'target' => '_blank',
              'class' => []
            ]
          ]
        ];
        $options[$id] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $SiteTypeDatas->getName() . ' | ',
          $link
        ];
      }
    }
    $form['update_entities'] = [
      '#type' => 'details',
      '#title' => 'Mise à jour',
      '#open' => true
    ];
    $form['update_entities']['update_clones'] = [
      '#type' => 'checkboxes',
      '#title' => 'Selectionner les clones à mettre à jour',
      '#options' => $options
    ];
    if ($site_internet_entity->isHomePage()) {
      $form['update_entities']['update_header'] = [
        '#type' => 'checkbox',
        '#title' => "Mettre à jour l'entete",
        '#description' => "Fonctionne dans le cadre de la mise à jour",
        '#default_value' => 1
      ];
      $form['update_entities']['update_footer'] = [
        '#type' => 'checkbox',
        '#title' => "Mettre à jour le pied de page",
        '#description' => "Fonctionne dans le cadre de la mise à jour",
        '#default_value' => 1
      ];
    }
    
    $form['actions'] = [
      '#type' => 'actions'
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Valider')
    ];
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $site_internet_entity = $form_state->get('site_internet_entity');
    $add_clone = $form_state->getValue('add_clone');
    $update_clones = $form_state->getValue('update_clones');
    if ($add_clone) {
      $ModeleDePage = $this->managerDuplicate->createClone($site_internet_entity->id());
      if ($ModeleDePage)
        $this->messenger()->addStatus("Un nouveau model de page a été creer " . $ModeleDePage->id());
    }
    if ($update_clones) {
      $update_header = (bool) $form_state->getValue('update_header');
      $update_footer = (bool) $form_state->getValue('update_footer');
      foreach ($update_clones as $id) {
        if ($id) {
          $entity = $this->managerDuplicate->updateClone($site_internet_entity->id(), $id, $update_header, $update_footer);
          if ($entity)
            $this->messenger()->addStatus(" Model de page $id Mise à jour");
        }
      }
    }
  }
  
}
