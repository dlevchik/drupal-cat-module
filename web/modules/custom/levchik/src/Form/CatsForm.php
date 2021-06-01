<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\levchik\Controller\LevchikController as LevchikController;

/**
 * Provides a levchik form.
 */
class CatsForm extends FormBase {

  /**
   * ID of the item to edit.
   *
   * @var int
   */
  protected $id;
  /**
   * Cat data object.
   *
   * @var object
   */
  protected $cat;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'levchik_cats';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    if (!is_null($id)) {
      $this->cat = LevchikController::getCats($this->id)[0];
    }
    $form['catName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your cat’s name:'),
      '#required' => TRUE,
      '#description' => $this->t('Min name length: 2, max: 32'),
      '#maxlength' => 32,
      '#default_value' => $this->cat ? $this->cat->name : "",
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
      '#default_value' => $this->cat ? $this->cat->email : "",
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
      '#default_value' => $this->cat ? [$this->cat->picture_fid] : "",
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save cat'),
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
    $cat = new \stdClass();
    $cat->name = $form_state->getValue('catName');
    $cat->email = $form_state->getValue('email');
    $cat->picture_fid = $form_state->getValue('cat_img')[0];
    if (!is_null($this->id)) {
      $this->editCat($cat);
    }
    else {
      $this->saveCat($cat);
    }
    $form_state->setRedirectUrl(Url::fromRoute('levchik.cats'));
  }

  /**
   * Saves cat data to db.
   *
   * @param object $cat
   *   Object with cat data.
   */
  public function saveCat(\stdClass $cat) {
    $connection = \Drupal::service('database');
    $file_fid = $cat->picture_fid;
    if ($file_fid) {
      $this->fileSavePermanent($file_fid);
    }
    $connection->insert('levchik')
      ->fields(['name', 'created', 'email', 'picture_fid'])
      ->values([
        'name' => $cat->name,
        'created' => \Drupal::time()->getRequestTime(),
        'email' => $cat->email,
        'picture_fid' => $file_fid ? $file_fid : 0,
      ])
      ->execute();
  }

  /**
   * Updates cat data from db.
   *
   * @param object $cat
   *   Object with cat data.
   */
  public function editCat(\stdClass $cat) {
    $connection = \Drupal::service('database');
    $file_fid = $cat->picture_fid;
    if ($file_fid) {
      $this->fileSavePermanent($file_fid);
    }
    $connection->update('levchik')
      ->condition('id', $this->id)
      ->fields([
        'name' => $cat->name,
        'email' => $cat->email,
        'picture_fid' => $file_fid ? $file_fid : 0,
      ])
      ->execute();
  }

  /**
   * Function to make fresh downloaded file permanent to drupal.
   *
   * @param int $fid
   *   File id to make permanent.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function fileSavePermanent(int $fid) {
    $file = File::load($fid);
    $file->setPermanent();
    $file->save();
  }

  /**
   * Valid email can only contain letters, underscore and a hyphen, @ + ".".
   *
   * @param string $email
   *   Email string to validate.
   *
   * @return bool
   *   If email is valid.
   */
  private function validateEmail(string $email) {
    return !preg_match('/[^a-zA-Z_@.-]/i', $email) && strlen($email) > 4;
  }

  /**
   * Validates email and displays message according to task standards.
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
   * Changes form on successful submit.
   */
  public function ajaxFunc(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!$form_state->hasAnyErrors()) {
      $response->addCommand(
        new HtmlCommand(
          '.block-system-main-block',
          $this->t("Thanks for your submission! Please refresh page to see the changes!"),
        ),
      );
    }
    return $response;
  }

}
