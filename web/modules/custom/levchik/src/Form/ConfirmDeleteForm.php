<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\levchik\Controller\LevchikController as LevchikController;

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
    LevchikController::deleteCat([$this->id]);
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
