<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

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

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#required' => TRUE,
      '#description' => $this->t('Valid email can only contain letters, underscore and a hyphen'),
      '#ajax' => [
        'callback' => '::validateEmailAjax',
        'effect' => 'fade',
        'event' => 'input',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add cat'),
      '#ajax' => [
        'callback' => '::ajaxFunc',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->clearErrors();
    if (mb_strlen($form_state->getValue('catName')) < 2 || mb_strlen($form_state->getValue('catName')) > 32) {
      $errText = $this->t("Cat's name should be at least 2 characters but less than 32 characters.");
      $form_state->setErrorByName('catName', $errText);
    }
    if (!$this->validateEmail($form_state->getValue('email'))) {
      $errText = $this->t('Email is not valid');
      $form_state->setErrorByName('email', $errText);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // $form_state->setRedirect('levchik.cats');
  }

  /**
   * {@inheritdoc}
   */
  private function validateEmail($email) {
    return !preg_match('/[^a-zA-Z_@.-]/i', $email) && strlen($email) > 4;
  }

  /**
   * {@inheritdoc}
   */
  public function validateEmailAjax(array $form, FormStateInterface $form_state) {
    $errText = $this->t('Email is not valid');
    $response = new AjaxResponse();
    if (!$this->validateEmail($form_state->getValue('email'))) {
      $response->addCommand(
        new InvokeCommand(
          '.form-item-email',
          'addClass',
          ['error'],
        ),
      );
      $response->addCommand(
        new HtmlCommand(
          '#edit-email--description',
          $errText,
        )
      );
    }
    else {
      $response->addCommand(
        new InvokeCommand(
          '.form-item-email',
          'removeClass',
          ['error'],
        ),
      );
      $response->addCommand(
        new HtmlCommand(
          '#edit-email--description',
          $form['email']['#description'],
        ),
      );
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxFunc(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!$form_state->hasAnyErrors()) {
      $response->addCommand(
        new HtmlCommand(
          '.levchik-cats',
          $this->t("Thank's for your submission!"),
        ),
      );
    }
    return $response;
  }

}
