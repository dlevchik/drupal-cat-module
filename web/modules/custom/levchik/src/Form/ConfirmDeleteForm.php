<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Defines a confirmation form to confirm deletion of cat by id.
 */
class ConfirmDeleteForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxFunc',
      'progress' => [
        'type' => 'throbber',
        'message' => NULL,
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = \Drupal::service('database');
    $query = $connection->select('levchik', 'lv');
    $query->condition('id', $this->id);
    $query->fields('lv', ['picture_fid']);
    $fid = $query->execute()->fetchAll()[0]->picture_fid;
    if ($fid != "0") {
      $file = File::load($fid);
      $file->delete();
    }
    $cat_deleted = $connection->delete('levchik')
      ->condition('id', $this->id)
      ->execute();
    $form_state->setRedirectUrl(Url::fromRoute('levchik.cats'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('levchik.cats');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete id%id cat?', ['%id' => $this->id]);
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxFunc(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!$form_state->hasAnyErrors()) {
      $response->addCommand(
        new HtmlCommand(
          '.confirm-delete-form',
          $this->t("Cat has been deleted!"),
        ),
      );
    }
    return $response;
  }

}
