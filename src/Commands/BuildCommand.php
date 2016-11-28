<?php

namespace drunomics\Phapp\Commands;

use drunomics\Phapp\Phapp;
use Robo\Tasks;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class BuildCommand.
 */
class BuildCommand extends Tasks {

  /**
   * The current phapp instance.
   *
   * @var Phapp
   */
  protected $phapp;

  /**
   * Ensures with a valid phapp instance to interact with.
   *
   * @hook validate build
   */
  public function initPhapp() {
    $this->phapp = Phapp::getInstance();
    $this->phapp->initShellEnvironment();
  }

  /**
   * Builds the complete project.
   *
   * If no branch is given, the currently checked out code is going to be built.
   *
   * @param string $branch
   *   If given, the branch will be checked out, built, and the build
   *   is going to be committed to the respective build branch "build/{BRANCH}".
   *
   * @option $auto-tag-prefix A prefix of Git tags to automtatically tag builds
   *   with. Only applicable if a branch is given.
   *
   * @return \Robo\Collection\Collection
   *
   * @command build
   */
  public function build($branch = NULL, $options = ['auto-tag-prefix' => 'version']) {
    if ($branch) {
      return $this->buildAndCommit($branch, $options);
    }
    else {
      return $this->doBuild($options);
    }
  }

  /**
   * Builds the project with the current code checkout.
   *
   * @param $options
   *   The command options.
   *
   * @return \Robo\Collection\Collection
   *   The collection containing the build commands.
   */
  protected function doBuild($options) {
    $collection = $this->collectionBuilder();

    $collection->addTaskToCollection(
      $this->taskExec($this->phapp->getCommand('build'))
    );

    return $collection;
  }

  /**
   * Helper to silently execute a command.
   *
   * @param string $command
   *   The command.
   *
   * @return \Symfony\Component\Process\Process
   */
  protected function _execSilent($command) {
    // Note that we cannot execute the task as regulary as this prints bold
    // red warnings when we do not want it to AND it stops on fails!
    // Because of that we execute the command directly with the symfony process
    // helper.
    // @todo: Figure out who to supress that and re-use taskExec() then.
    $process = new \Symfony\Component\Process\Process($command);
    $process->run();
    return $process;
  }

  /**
   * Builds the project and commits it.
   *
   * The build is going to be committed to the respective build branch
   * "build/{BRANCH}".
   *
   * @param string $branch
   *   The branch to check out and build.
   *
   * @return \Robo\Collection\Collection
   */
  protected function buildAndCommit($branch, $options = ['auto-tag-prefix' => 'version']) {
    $this->stopOnFail(TRUE);

    $previous_branch = $this->_execSilent("git rev-parse --abbrev-ref HEAD")->getOutput();
    $buildBranch = escapeshellarg("build/$branch");
    $branch = escapeshellarg($branch);
    $collection = $this->collectionBuilder();
    $symfony_fs = new \Symfony\Component\Filesystem\Filesystem();

    // Add the rollback action.
    $collection->rollback($this->taskGitStack()
      ->exec('git reset --hard')
      ->checkout($previous_branch));

    // Make sure the target branch exists.
    if ($this->_execSilent("git branch --list $branch | grep $branch")->getExitCode() != 0) {
      throw new InvalidArgumentException("The branch $branch does not exist.");
    }

    // Make sure the build branch exists.
    $branch_exists = $this->_execSilent("git branch --list $buildBranch | grep $buildBranch")->getExitCode() == 0;

    if ($branch_exists) {
      $collection->addTask(
        $this->taskGitStack()
          ->checkout($buildBranch)
          ->exec('reset --hard')
          ->merge($branch)
      );
    }
    else {
      $collection->addTask(
        $this->taskGitStack()
          ->exec("checkout -b $buildBranch")
          ->exec('reset --hard')
      );
    }

    // Handle .gitignore.
    if ($symfony_fs->exists('.build-gitignore')) {
      $this->say('Found .build-gitingore - applying it.');
      $collection->addTask(
        $this->taskExec('cp .build-gitignore .gitignore')
      );
    }
    else {
      $collection->addTask(
        $this->taskExec('rm .gitignore')
      );
    }

    // Now, as we are on a the clean build branch, start the build.
    $collection->addTask(
      $this->doBuild($options)
    );

    // And then commit it!
    $commit_hash = $this->_execSilent("git rev-parse HEAD")->getOutput();
    $commit_hash = trim($commit_hash);

    $task = $this->taskGitStack()
      ->exec("add -A")
      // Note that git commit uses ' already, so we remove ours. Also, we allow
      // empty commits in case no assets were changed the merge might be enough.
      ->commit(sprintf("Build %s commit %s.", trim($branch, '\''), trim($commit_hash, '\'')), '--allow-empty');

    $collection->addTask($task);

    $collection->completionCode(
      function() use ($buildBranch) {
        $this->say("Committed build to branch $buildBranch.");
      }
    );

    // Fetch tag.
    $tag = FALSE;
    if ($options['auto-tag-prefix']) {
      $prefix = escapeshellarg($options['auto-tag-prefix']);
      $result = $this->_execSilent("git tag --points-at $branch | grep $prefix", FALSE);
      if ($result->getExitCode() == 0) {
        $tag = $result->getOutput();
      }
    }

    if ($tag) {
      $target_tag = "build/$tag";
      $collection->addTask(
        $this->taskGitStack()
          ->tag($target_tag)
      );

      $collection->completionCode(
        function() use ($target_tag) {
          $this->say("Tagged build as $target_tag");
        }
      );
    }

    // Restore .gitignore and previous branch.
    $collection->completion(
      $this->taskGitStack()
        ->exec('git reset --hard')
        ->checkout($previous_branch)
    );
    return $collection;
  }

}
