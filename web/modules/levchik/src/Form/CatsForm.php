<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a levchik form.
 */
class CatsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'levchik_cats';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['catName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#required' => TRUE,
      '#description' => $this->t('Min name length: 2, max: 32'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add cat'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->clearErrors();
    if (mb_strlen($form_state->getValue('catName')) < 2 || mb_strlen($form_state->getValue('catName')) > 32) {
      $errText = $this->t('Cat\'s name should be at least 2 characters but less than 32 characters.');
      $this->messenger()->addError($errText);
      $form_state->setErrorByName('catName', $errText);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirect('levchik.cats');
  }

}
