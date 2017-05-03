<?php

namespace drunomics\Phapp\Commands;

use Robo\Tasks;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class CreateCommand.
 */
class CreateCommand extends Tasks {

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
   * @param string $name The created app's machine readable name.
   * @option string $template The template to use.
   * @option string $template-version A specific version of the template to use. Defaults to using the latest stable version.
   *
   * @command create
   */
  public function execute($name = NULL, $options = ['template' => NULL, 'template-version' => '*']) {
    if (!$name) {
      $name = $this->ask("App name (e.g. 'new-site'):");
    }

    if (empty($options['template'])) {
      $question = new ChoiceQuestion('Please select the app template to use:', array_values($this->templates), 1);

      $question->setErrorMessage('Answer %s is invalid.');
      $answer = $this->getDialog()
        ->ask($this->input(), $this->output(), $question);
      $template = array_search($answer, $this->templates);
    }
    else {
      $template = $options['template'];
    }

    $this->_exec("composer create-project $template:{$options['template-version']} --repository='https://packages.drunomics.com' $name");

    $repository_url_pattern = "git@bitbucket.org:drunomics/{{ app_name }}.git";
    $repository_url = str_replace('{{ app_name }}', $name, $repository_url_pattern);
    if ($this->confirm("Should the repostiory $repository_url be configured?")) {
      $this->_exec("cd $name &&
                git init &&
                git remote add origin $repository_url &&
                git add . &&
                git commit -am 'Initial version.' &&
                git branch -m develop");
      $this->say("Git repository has been initialized and pointed to <info>$repository_url</info>. Be sure the repository is set up and execute 'git push' once you are ready.");
    }
  }

}
