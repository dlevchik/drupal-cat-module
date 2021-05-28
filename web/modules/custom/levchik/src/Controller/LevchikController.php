<?php

namespace Drupal\levchik\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for levchik routes.
 */
class LevchikController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {
    $cats = $this->getCats();

    $build = [
      'content' => [
        '#type' => 'item',
        '#markup' => $this->t('Hello! You can add here a photo of your cat.'),
      ],
      'form' => \Drupal::formBuilder()->getForm('\Drupal\levchik\Form\CatsForm'),
      'cats' => [
        '#theme' => 'levchik-theme-hook',
        'cats' => $cats,
      ],
    ];

    return $build;
  }

  /**
   * Get's cats array from db.
   */
  public function getCats() {
    $database = \Drupal::database();
    $query = $database->select('levchik', 'lv');
    $query->fields('lv');
    $query->orderBy('created', 'DESC');

    $result = $query->execute()->fetchAll();
    return $result;
  }

}
