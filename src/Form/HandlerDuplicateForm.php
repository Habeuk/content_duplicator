<?php

namespace Drupal\content_duplicator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Content duplicator form.
 */
class HandlerDuplicateForm extends FormBase {
  
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
    $form['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => "Cette page dispose deja d'un ou plusieurs clone "
    ];
    $form['add_clone'] = [
      '#type' => 'checkbox',
      '#title' => 'ajouter un nouveau clone',
      '#default' => true
    ];
    $options = [];
    foreach ($ids as $id) {
      $SiteTypeDatas = \Drupal\creation_site_virtuel\Entity\SiteTypeDatas::load($id);
      if ($SiteTypeDatas)
        $options[$id] = $SiteTypeDatas->getName();
    }
    $form['update_clones'] = [
      '#type' => 'checkboxes',
      '#title' => 'Mettre à jour les clones',
      '#options' => $options
    ];
    
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
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $add_clone = $form_state->getValue('add_clone');
    $update_clones = $form_state->getValue('update_clones');
    if ($add_clone) {
      //
    }
    dd($update_clones, $add_clone);
  }
  
}
