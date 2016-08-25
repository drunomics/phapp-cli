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
   * Creates a new app based on a given template.
   *
   * @command create
   */
  public function createApp($name, $template = NULL) {
    $question = new ChoiceQuestion(
      'Please select the app template to use:',
      ['Empty', 'Drupal 8'],
      0
    );

    $question->setErrorMessage('Template %s is invalid.');
    $template = $this->getDialog()->ask($this->getInput(), $this->getOutput(), $question);
    $this->say('You have just selected: ' . $template);

    $this->yell('TODO: This command is unfinished.');
  }

}
