<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Exception\LogicException;
use drunomics\Phapp\PhappCommandBase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Contains git:* commands.
 */
class GitCommands extends PhappCommandBase  {

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
    // Make sure all remotes are there and update them.
    $this->setupGitRemotes(['remote' => $options['remote'], 'fetch' => TRUE, 'force' => FALSE]);

    if (!$branch) {
      $collection = $this->collectionBuilder()->getCollection();

      // Handle man development branch.
      $branch = $this->phappManifest->getGitBranchDevelop();
      if (!$this->branchExists($branch, 'origin')) {
        throw new LogicException("The development branch $branch does not exist in the repository yet. Nothing to pull from");
      }
      $collection->add(
        $this->pullBranch($branch, $options)
      );
      // Handle production branch. Ignore if it's not there yet as this might be
      // the case in a pre-production phase.
      $branch = $this->phappManifest->getGitBranchProduction();
      if ($this->branchExists($branch, 'origin')) {
        $collection->add(
          $this->pullBranch($branch, $options)
        );
      }
      return $collection;
    }
    else {
      return $this->pullBranch($branch, $options);
    }
  }

  /**
   * Pulls a single branch.
   *
   * Note: This assumes remotes are fetched already. Run setupGitRemotes() to
   * do so if needed.
   *
   * @param string $branch
   *   The branch to pull.
   * @option string $remote The remote to pull from. Defaults to all.
   */
  public function pullBranch($branch, $options = ['remote' => 'all']) {
    $this->ensureGitWorkspaceIsClean();
    $collection = $this->collectionBuilder()
      ->setVerbosityThreshold(OutputInterface::VERBOSITY_NORMAL)
      ->getCollection();
    $current_branch = trim($this->_execSilent("git rev-parse --abbrev-ref HEAD")->getOutput());

    $collection->addCode(function() use ($branch) {
      $this->say("Updating <info>$branch</info>...");
    });
    // Make one command per branch.
    $exec = [];

    foreach ($this->phappManifest->getGitRemotes() as $name => $url) {
      if (!($options['remote'] == 'all' || $options['remote'] == $name)) {
        continue;
      }
      // Silently ignore not existing branches at some remotes.
      if (!$this->branchExists($branch, $name)) {
        $collection->addCode(function() use ($branch, $name) {
          $this->io()->note("Branch $branch is not existing at remote $name.");
        });
        continue;
      }
      $exec[] = $this->updateBranchFromFetchedRemote($name, $branch, $current_branch);
    }

    // Note: We do not use robo's taskExecStack as this is to verbose and prints
    // commands twice.
    $task = $this->taskExec(implode(' && ', $exec));
    $collection->add($task);
    $exec = [];

    // Take care of build branches.
    foreach ($this->phappManifest->getGitBuildRepositories() as $name => $url) {
      if (!($options['remote'] == 'all')) {
        continue;
      }
      $build_branch = $this->phappManifest->getGitBranchForBuild($branch);
      $local_build_branch = $this->phappManifest->getGitBranchForBuildLocal($branch);

      // Silently ignore not existing build branches, they might not exist yet.
      if (!$this->branchExists($build_branch, $name)) {
        continue;
      }
      $exec[] = $this->updateBranchFromFetchedRemote($name, $local_build_branch, $current_branch, $build_branch);
    }

    if ($exec) {
      $collection->addCode(function() use ($build_branch) {
        $this->say("Updating branch of <info>$build_branch</info>...");
      });
      $task = $this->taskExec(implode(' && ', $exec));
      $collection->add($task);
    }
    return $collection;
  }

  /**
   * Updates the branch via a merge from the remote branch.
   *
   * @param $remote
   *   The remote alias.
   * @param $branch
   *   The branch to update, as named locally.
   * @param $current_branch
   *   The currently checked-out branch.
   * @param $remote_branch
   *   (optional) The branch name at the remote. If not passsed, the same branch
   *   name as locally will be assumed.
   *
   * @return string
   *   The command to update the branch.
   */
  protected function updateBranchFromFetchedRemote($remote, $branch, $current_branch, $remote_branch = NULL) {
    $remote_branch = $remote_branch ?: $branch;
    if ($current_branch == $branch) {
      return "git merge $remote/$remote_branch --no-stat --no-edit --quiet";
    }
    else {
      // 1. git fetch will fail if the remote branch is older than the local
      // branch. Ignore the fail if this is the case, but the branch is merged.
      // 2. We cannot just do git fetch REMOTE branch:branch as we do not want
      // to fetch the branch again, thus pass $PWD as remote and refer to the
      // remote branch directly.
      // See https://stackoverflow.com/questions/6777629/merge-branches-without-checking-out-branch
      return "(git fetch \$PWD $remote/$remote_branch:$branch -q || git merge-base --is-ancestor $remote/$remote_branch $branch)";
    }
  }

  /**
   * Configures Git remote repositories.
   *
   * @option force Overwrite existing remotes if any.
   * @option fetch Fetch the remote repositories after setup.
   *
   * @command git:setup-remotes
   */
  public function setupGitRemotes($options = ['force' => FALSE, 'fetch' => FALSE]) {
    $remotes = $this->phappManifest->getGitRemotes() + $this->phappManifest->getGitBuildRepositories();
    foreach ($remotes as $name => $url) {
      $result = $this->_execSilent('git remote get-url ' . $name);
      if ($result->getExitCode() == 0 && trim($result->getOutput()) != trim($url)) {
        if (empty($options['force'])) {
          throw new LogicException("Remote $name already exists but does not point to $url. Re-run git:setup-remotes with --force to fix that.");
        }
        else {
          $this->_exec("git remote set-url $name $url");
        }
      }
      elseif ($result->getExitCode() > 0) {
        $this->_exec("git remote add $name $url");
      }
      else {
        $this->say("Remote <info>$name</info> already present.");
      }
      if ($options['fetch']) {
        $this->_exec("git fetch $name");
      }
    }
  }

  /**
   * Ensures the current git workspace is clean.
   */
  public function ensureGitWorkspaceIsClean() {
    $result = $this->_execSilent('/bin/bash -c "test -n "$(git status --porcelain)""');
    if ($result->getExitCode() != 0) {
      throw new \LogicException("Git workspace is dirty: \n" .
        $this->_execSilent('git status --porcelain')->getOutput());
    }
  }

  /**
   * Determines whether a branch exists in a given repository.
   *
   * Note: This assumes remotes are fetched already. Run setupGitRemotes() to
   * do so if needed.
   *
   * @param string $branch
   *   The branch name.
   * @param string $repository
   *   (optional) The remote name or repository url. Defaults to 'origin'.
   *
   * @return bool
   */
  public function branchExists($branch, $repository = 'origin') {
    $result = $this->_execSilent("/bin/bash -c 'git branch --list -r $repository/$branch | grep $branch -q'");
    return $result->getExitCode() == 0;
  }

}
