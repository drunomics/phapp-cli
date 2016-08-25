<?php

namespace drunomics\Phapp\Commands;

use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\LoadAllTasks;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class CreateCommand.
 */
class CreateCommand {

  use ContainerAwareTrait;
  use LoadAllTasks;
  use IO;

  /**
   * @todo: Add config.yml for basic config like this.
   *
   * @var array
   */
  protected $templates = [
    'drunomics/php-project' => 'drunomics/php-project (PHP or any web project - TODO)',
    'drunomics/drupal-project' => 'drunomics/drupal-project (Drupal 8)'
  ];

  /**
   * Creates a new app based on a given template.
   *
   * @command create
   */
  public function createApp($name, $template = NULL, $options = ['template' => NULL, 'template-version' => '*']) {
    if (empty($options['template'])) {
      $question = new ChoiceQuestion('Please select the app template to use:', array_values($this->templates), 1);

      $question->setErrorMessage('Answer %s is invalid.');
      $answer = $this->getDialog()
        ->ask($this->getInput(), $this->getOutput(), $question);
      $template = array_search($answer, $this->templates);
    }
    else {
      $template = $options['template'];
    }

    $this->_exec("composer create-project $template:{$options['template-version']} --repository='https://packages.drunomics.com' $name");
  }

}
