<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

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
      '#maxlength' => 32,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#required' => TRUE,
      '#description' => $this->t('Valid email can only contain letters, underscore and a hyphen'),
      '#ajax' => [
        'callback' => '::validateEmailAjax',
        'effect' => 'fade',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    $form['cat_img'] = [
      '#type' => 'managed_file',
      '#name' => 'cat_img',
      '#title' => t("Your cat's photo:"),
      '#required' => TRUE,
      '#size' => 2,
      '#upload_validators' => [
        'file_validate_is_image' => [],
        'file_validate_size' => [2097152],
        'file_validate_extensions' => ['gif jpg jpeg'],
      ],
      '#upload_location' => 'public://levchik/',
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
    $connection = \Drupal::service('database');
    $file_fid = $form_state->getValue('cat_img')[0];
    if ($file_fid) {
      $file = File::load($file_fid);
      $file->setPermanent();
      $file->save();
    }
    $connection->insert('levchik')
      ->fields(['name', 'created', 'email', 'picture_fid'])
      ->values([
        'name' => $form_state->getValue('catName'),
        'created' => \Drupal::time()->getRequestTime(),
        'email' => $form_state->getValue('email'),
        'picture_fid' => $file_fid ? $file_fid : 0,
      ])
      ->execute();
    // \Drupal\levchik\Controller\LevchikController::getCats()[0];
    $form_state->setRedirectUrl(Url::fromRoute('levchik.cats'));
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
          '.block-system-main-block',
          $this->t("Thank's for your submission! Please refresh page to see the changes!"),
        ),
      );
    }
    return $response;
  }

}
