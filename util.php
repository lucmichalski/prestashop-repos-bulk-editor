<?php

/**
 * @param \Matks\PrestaShopRepoBulkEditor\BranchManager $branchManager
 * @param $repositoryName
 * @return string
 * @throws \Matks\PrestaShopRepoBulkEditor\ContinueException
 */
function findBaseBranch(\Matks\PrestaShopRepoBulkEditor\BranchManager $branchManager, $repositoryName)
{
    $baseBranch = $branchManager->findRepositoryBaseBranch($repositoryName);
    echo '* Found base branch ' . $baseBranch . ' for prestashop:' . $repositoryName . PHP_EOL;

    if ($baseBranch === null) {
        echo '* Could not find base branch for repo prestashop :' . $repositoryName . PHP_EOL;
        throw new \Matks\PrestaShopRepoBulkEditor\ContinueException();
    }
    return $baseBranch;
}

/**
 * @param \Github\Client $client
 * @param $repositoryName
 * @param $path
 * @param $baseBranch
 * @throws \Matks\PrestaShopRepoBulkEditor\ContinueException
 */
function checkFileExists(\Matks\PrestaShopRepoBulkEditor\RepositoryFilesManager $filesManager, $repositoryName, $path, $baseBranch)
{
    $fileExists = $filesManager->checkFileExists('prestashop', $repositoryName, $path, $baseBranch);
    if ($fileExists) {
        echo '* Target file already exists for ' . $repositoryName . PHP_EOL;
        throw new \Matks\PrestaShopRepoBulkEditor\ContinueException();
    }
}

/**
 * @param \Matks\PrestaShopRepoBulkEditor\ForkManager $forkManager
 * @param $repositoryName
 */
function checkForkExistsAndCreateIfNeeded(
    \Matks\PrestaShopRepoBulkEditor\ForkManager $forkManager,
    $repositoryName)
{
    $forkExists = $forkManager->checkForkExists($repositoryName);

    if (!$forkExists) {
        echo '* Fork does not exist | matks:' . $repositoryName . PHP_EOL;
        echo '* Attempting to create fork ...' . PHP_EOL;
        $forkManager->createFork($repositoryName);
        echo '* Fork successfully created | matks:' . $repositoryName . PHP_EOL;
    }
}

/**
 * @param $repositoryName
 * @param $baseBranch
 * @param $pullRequestTitle
 * @param \Matks\PrestaShopRepoBulkEditor\RepositoryFilesManager $filesManager
 * @param $path
 * @param $content
 * @param \Matks\PrestaShopRepoBulkEditor\PullRequestsManager $pullRequestManager
 * @throws \Github\Exception\MissingArgumentException
 */
function createPRToCreateFile(
    $repositoryName,
    $baseBranch,
    $pullRequestMessage,
    $pullRequestTitle,
    \Matks\PrestaShopRepoBulkEditor\RepositoryFilesManager $filesManager,
    $path,
    $content,
    \Matks\PrestaShopRepoBulkEditor\PullRequestsManager $pullRequestManager)
{
    $debug = true;

    echo sprintf(
            '\o/ Creating PR for repo %s %s => %s',
            $repositoryName,
            'matks:' . $baseBranch,
            'prestashop:' . $baseBranch
        ) . PHP_EOL;

    $commitMessage = $pullRequestTitle;

    if ($debug) {
        echo ' - Create file on repo'.PHP_EOL;
    }

    $fileInfo = $filesManager->createFileOnRepo('matks', $repositoryName, $path, $content, $commitMessage, $baseBranch);

    if ($debug) {
        echo ' - Create PR'.PHP_EOL;
    }

    try {
        $pullRequestManager->createPR('prestashop', $repositoryName, $baseBranch, $pullRequestTitle, $pullRequestMessage);
    } catch (Github\Exception\RuntimeException $e) {
        echo '!!! Failed to create PR for prestashop:' . $repositoryName . PHP_EOL;
    }
}

