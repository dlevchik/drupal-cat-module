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

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Hello! You can add here a photo of your cat.'),
    ];

    return $build;
  }

}
