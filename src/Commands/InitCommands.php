<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Exception\LogicException;
use drunomics\Phapp\PhappCommandBase;
use drunomics\Phapp\PhappManifest;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Inits phapp.yml for new projects.
 */
class InitCommands extends PhappCommandBase  {

  /**
   * {@inheritdoc}
   */
  protected $requiresPhappManifest = FALSE;

  /**
   * Initializes a new phapp.yml for your project.
   *
   * @param string $name
   *   (optional) The project's machine readable name.
   *
   * @command init:manifest
   */
  public function initManifest($name = '') {
    $instance = PhappManifest::discoverInstance();
    if ($instance) {
      throw new LogicException("There is a already a phapp.yml file, aborting.");
    }
    if (!$name) {
      $name = $this->askDefault("Phapp name:", basename(getcwd()));
    }
    $repo = $this->askDefault("Git repository url:", $this->globalConfig->getGitUrlPattern($name));

    $data['name'] = $name;
    $data['git']['url' ] = $repo;
    $data += $this->globalConfig->getPhappInitDefaults();
    (new Filesystem())->dumpFile('phapp.yml', Yaml::dump($data, 2, 2, YAML::DUMP_MULTI_LINE_LITERAL_BLOCK));
    $this->io()->success("Your new phapp.yml file has been written to the current directory.");
  }

}
