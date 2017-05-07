<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\GlobalConfig;
use drunomics\Phapp\PhappCommandBase;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class CreateCommand.
 */
class CreateCommand extends PhappCommandBase {

  /**
   * Ensures with a valid phapp definition to interact with.
   *
   * @hook validate
   */
  public function initPhapp() {
    $this->globalConfig = GlobalConfig::discoverConfig();
  }

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
    $this->stopOnFail(TRUE);
    $this->initPhapp();
    if (!$name) {
      $name = $this->ask("Phapp name (e.g. 'new-app'):");
    }

    if (empty($options['template'])) {
      $choices = [];
      foreach ($this->globalConfig->getPhappTemplatePackages() as $package => $description) {
        $choices[$package] = "$package - $description";
      }
      $question = new ChoiceQuestion('Please select the app template to use:', array_values($choices), 1);

      $question->setErrorMessage('Answer %s is invalid.');
      $answer = $this->getDialog()
        ->ask($this->input(), $this->output(), $question);
      $template = array_search($answer, $choices);
    }
    else {
      $template = $options['template'];
    }

    if ($package_repository = $this->globalConfig->getComposerRepository()) {
      $args = " --repository='$package_repository'";
    }
    else {
      $args = '';
    }
    $this->_exec("composer create-project $template:{$options['template-version']} $args $name");

    $repository_url = str_replace('{{ phapp_name }}', $name, $this->globalConfig->getGitUrlPattern());
    if ($this->confirm("Should a new Git repository be initialized and pointed to $repository_url?")) {
      $dev_branch = $this->phappManifest->getGitBranchDevelop();
      $this->_exec("cd $name &&
                git init &&
                git remote add origin $repository_url &&
                git add . &&
                git commit -am 'Initial version.' &&
                git branch -m $dev_branch");
      $this->say("Git repository has been initialized and pointed to <info>$repository_url</info>. Be sure the repository is set up and execute 'git push' once you are ready.");
    }
  }

}
