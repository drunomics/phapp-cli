<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Exception\LogicException;
use drunomics\Phapp\Exception\PhappInstanceNotFoundException;
use drunomics\Phapp\PhappCommandBase;
use drunomics\Phapp\PhappManifest;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

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
    $target = str_replace('~', getenv('HOME'), $target);
    if ((new Filesystem())->exists($target)) {
      throw new LogicException("Target directory $target already exists.");
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
    $this->say("Registering global composer config...");
    $this->globalConfig->applyGlobalComposerConfig();

    $this->say("Creating the project...");
    $this->_exec("composer create-project $template:{$options['template-version']} $target");

    // There must be a phapp.yml file if not create it now.
    chdir($target);
    $this->updatePhappManifest($name);
    $repository_url = $this->phappManifest->getGitUrl();

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

  /**
   * Updates the Phapp manifest based upon global defaults.
   *
   * @param string $name
   *   The project name.
   */
  protected function updatePhappManifest($name) {
    $this->say("Generating phapp.yml...");
    try {
      $this->phappManifest = PhappManifest::getInstance();
      $data = (new Parser())->parse(file_get_contents($this->phappManifest->getFile()->getRealPath()));
    }
    catch (PhappInstanceNotFoundException $e) {
      $data = [];
    }
    $data = array_replace_recursive($data, $this->globalConfig->getPhappInitDefaults());
    $data['name'] = $name;
    $data['git']['url' ] = $this->globalConfig->getGitUrlPattern($name);
    (new Filesystem())->dumpFile('phapp.yml', Yaml::dump($data, 2, 2, YAML::DUMP_MULTI_LINE_LITERAL_BLOCK));
    $this->phappManifest = PhappManifest::getInstance();
  }

}
