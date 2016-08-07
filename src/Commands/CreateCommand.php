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
   * Clones an app.
   *
   * @command clone
   */
  public function cloneApp() {

  }

  /**
   * Creates a new app based on a given template.
   *
   * @command create
   */
  public function createApp($name, $template = NULL) {
    $helper = $this->getDialog()->getHelperSet()->get('question');

    $question = new ChoiceQuestion(
      'Please select the app template to use:',
      ['Empty', 'Drupal 8'],
      0
    );
    $question->setErrorMessage('Template %s is invalid.');
    $template = $helper->ask($this->getInput(), $this->getOutput(), $question);
    $this->writeln('You have just selected: ' . $template);
  }

  /**
   * Initializes a new app.
   */
  public function initApp() {

  }
}
