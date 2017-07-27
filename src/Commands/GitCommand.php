<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Exception\LogicException;
use drunomics\Phapp\PhappCommandBase;

/**
 * Contains git:* commands.
 */
class GitCommand extends PhappCommandBase  {

  /**
   * {@inheritdoc}
   */
  protected $requiresPhappManifest = TRUE;

  /**
   * Updates local branches by pull from remote repositories.
   *
   * @param string $branch
   *   (optional) The branch to pull. If none is given, both the projects
   *   development and production branches will be updated.
   * @option string $remote The remote to pull from. Detauls to all.
   *
   * @command git:pull
   */
  public function pullBranches($branch = NULL, $options = ['remote' => 'all']) {
    $this->setupGitRemotes();
    if (!$branch) {
      $collection = $this->collectionBuilder()->getCollection();
      $collection->add(
        $this->pullBranch($this->phappManifest->getGitBranchDevelop(), $options)
      );
      $collection->add(
        $this->pullBranch($this->phappManifest->getGitBranchProduction(), $options)
      );
      return $collection;
    }
    else {
      return $this->pullBranch($branch, $options);
    }
  }

  /**
   * Pulls a single branch.
   *
   * @param string $branch
   *   The branch to pull.
   * @option string $remote The remote to pull from. Defaults to all.
   *
   * @command false
   */
  public function pullBranch($branch, $options = ['remote' => 'all']) {
    $this->ensureGitWorkspaceIsClean();
    $collection = $this->collectionBuilder()->getCollection();
    $current_branch = trim($this->_execSilent("git rev-parse --abbrev-ref HEAD")->getOutput());

    $remotes = [
      'origin' => $this->phappManifest->getGitUrl(),
    ]
    + $this->phappManifest->getGitMirrors();

    foreach ($remotes as $name => $url) {
      if (!($options['remote'] == 'all' || $options['remote'] == $name)) {
        continue;
      }
      $collection->addCode(function() use ($name) {
        $this->say("Pulling from <info>$name</info>...");
      });

      if ($current_branch == $branch) {
        $task = $this->taskExec("git pull $name $branch -n");
      }
      else {
        $task = $this->taskExec("git fetch $name $branch:$branch");
      }
      $collection->add($task);
    }

    // Take care of build branches.
    foreach ($this->phappManifest->getGitBuildRepositories() as $url) {
      if (!($options['remote'] == 'all' || (isset($remotes[$options['remote']]) && $remotes[$options['remote']] == $url))) {
        continue;
      }
      $build_branch = $this->phappManifest->getGitBranchForBuild($branch);
      $local_build_branch = $this->phappManifest->getGitBranchForBuildLocal($branch);

      // Check whether the branch has a build branch.
      $result = $this->_execSilent("/bin/bash -c 'git ls-remote --heads $url $build_branch | grep $build_branch -q'");
      if ($result->getExitCode() > 0) {
        continue;
      }

      $collection->addCode(function() use ($url) {
        $this->say("Pulling build branch from <info>$url</info>...");
      });

      if ($current_branch == $local_build_branch) {
        $task = $this->taskExec("git pull $url $build_branch -n");
      }
      else {
        $task = $this->taskExec("git fetch $url $local_build_branch:$build_branch");
      }
      $collection->add($task);
    }

    return $collection;
  }

  /**
   * Configures Git remote repositories.
   *
   * @option force Overwrite existing remotes if any
   *
   * @command git:setup
   */
  public function setupGitRemotes($options = ['force' => FALSE]) {
    $remotes = [
      'origin' => $this->phappManifest->getGitUrl(),
    ] + $this->phappManifest->getGitMirrors();
    foreach ($remotes as $name => $url) {
      $result = $this->_execSilent('git remote get-url ' . $name);
      if ($result->getExitCode() == 0 && trim($result->getOutput()) != trim($url)) {
        if (!$options['force']) {
          throw new LogicException("Remote $name already exists but does not point to $url.");
        }
        else {
          $this->_exec("git remote set-url $name $url");
        }
      }
      elseif ($result->getExitCode() > 0) {
        $this->_exec("git remote add $name $url");
      }
      else {
        $this->say("Remote $name already present.");
      }
    }
  }

  /**
   * Ensures the current git workspace is clean.
   *
   * @command false
   */
  public function ensureGitWorkspaceIsClean() {
    $result = $this->_execSilent('/bin/bash -c "test -n "$(git status --porcelain)""');
    if ($result->getExitCode() != 0) {
      throw new \LogicException("Git workspace is dirty: \n" .
        $this->_execSilent('git status --porcelain')->getOutput());
    }
  }

}
