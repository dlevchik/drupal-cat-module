<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\levchik\Controller\LevchikController as LevchikController;

/**
 * Provides a levchik administer cats page form.
 */
class CatsAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'levchik_cats_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cats_rows = [];
    $cats = LevchikController::getCats();
    $renderer = \Drupal::service('renderer');
    foreach ($cats as $cat) {
      $image = [
        '#theme' => 'image',
        '#uri' => $cat->picture_src,
        '#alt' => $this->t("A cat."),
        '#height' => 75,
        '#width' => 75,
      ];
      $buttons = [
        '#theme' => 'levchik_cats_button',
        '#id' => $cat->id,
      ];
      $cats_rows[$cat->id] = [
        $cat->name,
        $cat->email,
        $renderer->render($image),
        $renderer->render($buttons),
      ];
    }
    $form['cats'] = $cats_rows ? [
      '#type' => 'tableselect',
      '#caption' => $this->t('Your cute cats'),
      '#header' => [
        $this->t('Cat Name'),
        $this->t('Owner email'),
        $this->t('Cat Pic'),
        $this->t('Actions'),
      ],
      '#options' => $cats_rows,
    ] : [
      '#markup' => "<h2>Sorry. No Cats found here:(</h2>",
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#states' => [
        'visible' => [
          'form' => ['filled' => 'true'],
        ],
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete this cats'),
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = array_filter($form_state->getValues()['cats']);
    LevchikController::deleteCat(array_keys($values));
    $form_state->setRedirect('levchik.cats_list');
  }

}
