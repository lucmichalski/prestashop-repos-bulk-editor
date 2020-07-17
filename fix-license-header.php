<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/util.php';

// -------------- SETUP -------------- //

$client = new \Github\Client();
$token = file_get_contents(__DIR__ . '/token.txt');
$client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);

$pullRequestTitle = 'Fix license headers - part 2';
$pullRequestMessage = 'Fix (again) license headers' . PHP_EOL . PHP_EOL . 'This PR is created automatically'
    . ' by [Matks PrestaShop Repositories Bulk Editor](https://github.com/matks/prestashop-repos-bulk-editor)';


$forkManager = new \Matks\PrestaShopRepoBulkEditor\ForkManager($client);
$branchManager = new \Matks\PrestaShopRepoBulkEditor\BranchManager($client);
$filesManager = new \Matks\PrestaShopRepoBulkEditor\RepositoryFilesManager($client);
$pullRequestManager = new \Matks\PrestaShopRepoBulkEditor\PullRequestsManager($client);
$licenseHeaderFixer = new \Matks\PrestaShopRepoBulkEditor\LicenseHeaderFixer($client);

$modulesToProcess = require_once __DIR__ . '/modulesList.php';
$workBranchName = 'fix-license-headers-2';

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

    // CHECK 3 the right branch exists on fork
    $branchAlreadyExists = $branchManager->checkBranchExistsWithName('matks', $repositoryName, $baseBranch);
    if (!$branchAlreadyExists) {
        echo '* Fork matks:' . $repositoryName . ' does not have branch ' . $baseBranch . PHP_EOL;
        continue;
    }

    // CHECK 4 check there is no PR already doing the add
    $pullRequestExists = $pullRequestManager->checkPRExistsWithName('prestashop', $repositoryName, $pullRequestTitle);
    if ($pullRequestExists) {
        echo '* PR already exists for ' . $repositoryName . PHP_EOL;
        continue;
    }

    // CHECK 5 the work branch does not exist yet on fork
    $branchAlreadyExists = $branchManager->checkBranchExistsWithName('matks', $repositoryName, $workBranchName);
    if ($branchAlreadyExists) {
        echo '* Fork matks:' . $repositoryName . ' work branch already exists: ' . $baseBranch . PHP_EOL;
        continue;
    }

    // READY TO WORK

    // create branch
    $result = $branchManager->createBranchFrom('matks', $repositoryName, $baseBranch, $workBranchName);
    if (!$result) {
        echo '* Fork matks:' . $repositoryName . ' failed to create work branch ' . $baseBranch . PHP_EOL;
        continue;
    }

    // update the branch with fixed license headers
    $licenseHeaderFixer->scanDir($repositoryName, $workBranchName);

    // create the PR
    createPRToMergeBranch(
        $repositoryName,
        $baseBranch,
        $workBranchName,
        $pullRequestMessage,
        $pullRequestTitle,
        $pullRequestManager
    );
}
