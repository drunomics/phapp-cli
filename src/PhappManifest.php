<?php

namespace drunomics\Phapp;

use drunomics\Phapp\Exception\PhappInstanceNotFoundException;
use drunomics\Phapp\Exception\PhappManifestMalformedException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

/**
 * Provides information about a local phapp instance.
 */
class PhappManifest {

  /**
   * Information about the manifest file.
   *
   * @var \Symfony\Component\Finder\SplFileInfo
   */
  protected $file;

  /**
   * The content of the config file.
   *
   * @var mixed[]
   */
  protected $config;

  /**
   * Finds a phapp manifest based upon the current working directory.
   *
   * @return \SplFileInfo|null
   *   The info about the manifest file or NULL if no instance can be found.
   */
  public static function discoverInstance() {
    $finder = new Finder();

    // @todo: Add multiple parent Git dirs here.
    $finder->files()->name('phapp.yml')->in(getcwd())->depth('== 0');

    if ($finder->count() == 0) {
      return;
    }
    // Just use the first phapp file found.
    foreach ($finder as $file) {
      break;
    }
    return $file;
  }

  /**
   * Gets a phapp manifest for the given directory.
   *
   * If not directory is given, the right phapp instance is discovered based
   * upon the current working directory.
   *
   * @param string $directory
   *   (optional) The directory of which to read the phapp manifest from.
   *
   * @return static
   *   The phapp manifest instance.
   *
   * @throws \Symfony\Component\Yaml\Exception\ParseException
   *   Thrown if the phapp.yml file found is invalid.
   * @throws \drunomics\Phapp\Exception\PhappInstanceNotFoundException
   *   If no phapp.yml could be found.
   */
  public static function getInstance($directory = NULL) {
    if (isset($directory)) {
      $file = new \SplFileInfo($directory . '/phapp.yml');
      if (!$file->isFile()) {
        throw new PhappInstanceNotFoundException('Uanble to find phapp.yml at '. $directory);
      }
    }
    else {
      $file = static::discoverInstance();
      if (!$file || !$file->isFile()) {
        throw new PhappInstanceNotFoundException($directory);
      }
    }
    $yamlParser = new Parser();
    $config = $yamlParser->parse(file_get_contents($file->getRealPath()));
    return new static($config, $file);
  }

  /**
   * Constructs the object.
   *
   * @param array $config
   *   The parsed phapp.yml file contents.
   * @param \SplFileInfo $configFile
   *   The config file info.
   *
   * @throws \drunomics\Phapp\Exception\PhappManifestMalformedException
   *   Thrown when validation fails.
   */
  public function __construct(array $config, \SplFileInfo $configFile) {
    $yamlParser = new Parser();
    $default_config = $yamlParser->parse(file_get_contents(__DIR__ . '/../defaults/phapp.defaults.yml'));
    $this->config = array_replace_recursive($default_config, $config);
    $this->file = $configFile;
    $this->validate();
  }

  /**
   * Validates the phapp manifest.
   *
   * @throws PhappManifestMalformedException
   *   Thrown when validation fails.
   */
  public function validate() {
    if (empty($this->config['name'])) {
      throw new PhappManifestMalformedException('Phapp name is required.');
    }
    if (!preg_match('/^[a-z0-9_-]+$/', $this->config['name'])) {
      throw new PhappManifestMalformedException('Phapp name may only contain lowercase alpha-numeric characters, dashes and underscores.');
    }
  }

  /**
   * Gets information about the phapp.yml file.
   *
   * @return \Symfony\Component\Finder\SplFileInfo
   */
  public function getFile() {
    return $this->file;
  }

  /**
   * Gets the app's name.
   *
   * @return string
   */
  public function getName() {
    return $this->config['name'];
  }

  /**
   * Gets the environment variables.
   *
   * @return array
   */
  public function getEnvironment() {
    return $this->config['environment'] ?? [];
  }

  /**
   * Gets all directories containing sub-apps.
   *
   * @return string[]
   *   The relative directories containing sub-apps.
   */
  public function getSubAppDirectories() {
    return $this->config['sub_apps'];
  }

  /**
   * Gets the bash string configured for a given command.
   *
   * @param string $name
   *   The name of the command; e.g. 'build' or 'deploy'.
   *
   * @return string|null
   *   The configured command string.
   */
  public function getCommand($name) {
    if (isset($this->config['commands'][$name])) {
      return $this->config['commands'][$name];
    }
    return NULL;
  }

  /**
   * Gets the Git repository URL.
   *
   * @return string
   */
  public function getGitUrl() {
    return $this->config['git']['url'];
  }

  /**
   * Gets the URLs of all Git repository mirrors, if any.
   *
   * @return string[]
   *   An array of git urls, keyed by remote names.
   */
  public function getGitMirrors() {
    return $this->config['git']['mirrors'];
  }

  /**
   * Gets the URLs of all Git repositories.
   *
   * @return string[]
   *   An array of git urls, keyed by remote names.
   */
  public function getGitRemotes() {
    return [
      'origin' => $this->getGitUrl(),
    ]
    + $this->getGitMirrors();
  }

  /**
   * Gets the URLs of all Git repository mirrors that contain builds.
   *
   * @return string[]
   *   An array of git urls, keyed by remote names.
   */
  public function getGitBuildRepositories() {
    if ($this->config['git']['build_repositories'] == 'all') {
      return $this->getGitRemotes();
    }
    else {
      return $this->config['git']['build_repositories'];
    }
  }

  /**
   * Gets the name of the production branch, usually 'master'.
   *
   * @return string
   */
  public function getGitBranchProduction() {
    return $this->config['git']['branches']['production'];
  }

  /**
   * Gets the prefix used for the tags of released versions.
   *
   * @return string
   */
  public function getGitVersionTagPrefix() {
    return $this->config['git']['branches']['version_prefix'];
  }

  /**
   * Gets the name of the respective build branch, usually 'build/$BRANCH'.
   *
   * @return string
   */
  public function getGitBranchForBuild($source_branch) {
    return $this->config['git']['branches']['build_prefix'] .  $source_branch;
  }

  /**
   * Gets the local name of the respective build branch.
   *
   * Usually this is the same as the regular, remote build branch. However, if
   * the prefix is empty the local branch differs from the remtoe branch.
   *
   * @return string
   */
  public function getGitBranchForBuildLocal($source_branch) {
    if (!$this->config['git']['branches']['build_prefix']) {
      return 'build/' .  $source_branch;
    }
    return $this->getGitBranchForBuild($source_branch);
  }

  /**
   * Gets the name of the develop branch, usually 'develop'.
   *
   * @return string
   */
  public function getGitBranchDevelop() {
    return $this->config['git']['branches']['develop'];
  }

}
