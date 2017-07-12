<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\PhappCommandBase;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Creates a new project.
 */
class CreateCommand extends PhappCommandBase {

  /**
   * {@inheritdoc}
   */
  protected $requiresPhappManifest = FALSE;

  /**
   * Creates a new project base on a given template.
   *
   * @param string $name
   *   The created project's machine readable name.
   * @param string $target
   *   (optional) The directory to create the project in. If not given, the
   *   project is created in the configured default directory path.
   * @option string $template The template to use.
   * @option string $template-version A specific version of the template to use. Defaults to using the latest stable version.
   *
   * @command create
   */
  public function execute($name = NULL, $target = NULL, $options = ['template' => NULL, 'template-version' => '*']) {
    if (!$name) {
      $name = $this->ask("Phapp name (e.g. 'new-app'):");
    }
    if (!isset($target)) {
      $target = $this->globalConfig->getDefaultDirectoryPath($name);
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
    $this->_exec("composer create-project $template:{$options['template-version']} $args $target");

    $repository_url = $this->globalConfig->getGitUrlPattern($name);
    if ($this->confirm("Should a new Git repository be initialized and pointed to $repository_url?")) {
      $dev_branch = $this->phappManifest->getGitBranchDevelop();
      $this->_exec("cd $target &&
                git init &&
                git remote add origin $repository_url &&
                git add . &&
                git commit -am 'Initial version.' &&
                git branch -m $dev_branch");
      $this->say("Git repository has been initialized and pointed to <info>$repository_url</info>. Be sure the repository is set up and execute 'git push' once you are ready.");
    }
  }

}
