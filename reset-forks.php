<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/util.php';

// -------------- SETUP -------------- //

$client = new \Github\Client();
$token = file_get_contents(__DIR__ . '/token.txt');
$client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);

$forkManager = new \Matks\PrestaShopRepoBulkEditor\ForkManager($client);
$branchManager = new \Matks\PrestaShopRepoBulkEditor\BranchManager($client);

$modulesToProcess = require_once __DIR__ . '/modulesList.php';

foreach ($modulesToProcess as $moduleToProcess) {
    $repositoryName = $moduleToProcess;
    echo 'Analyzing repo prestashop:' . $repositoryName . PHP_EOL;

    // --------------  CHECKS --------------
    // CHECK 1 check fork exists
    checkForkExistsAndCreateIfNeeded($forkManager, $repositoryName);

    // CHECK 2 is base branch identifiable
    try {
        $baseBranch = findBaseBranch($branchManager, $repositoryName);
    } catch (\Matks\PrestaShopRepoBulkEditor\ContinueException $e) {
        continue;
    }

    // CHECK 2 the right branch exists on fork
    $branchAlreadyExists = $branchManager->checkBranchExistsWithName('matks', $repositoryName, $baseBranch);
    if (!$branchAlreadyExists) {
        echo '* Fork matks:' . $repositoryName . ' does not have branch ' . $baseBranch . PHP_EOL;
        $needToDeleteBranch = false;
    } else {
        echo '* Fork matks:' . $repositoryName . ' has branch ' . $baseBranch . PHP_EOL;
        $needToDeleteBranch = true;
    }

    // READY TO WORK

    // delete fork branch
    if ($needToDeleteBranch) {
        $branchManager->deleteBranch('matks', $repositoryName, $baseBranch);
        echo '* Deleted branch ' . $baseBranch . ' for fork matks:' . $repositoryName . PHP_EOL;
    }

    $branchDeleted = $branchManager->pullUpstreamBranchIntoFork('matks', $repositoryName, $baseBranch);
    echo '* Created branch ' . $baseBranch . ' for fork matks:' . $repositoryName .
        ' from upstream' . PHP_EOL;
}
