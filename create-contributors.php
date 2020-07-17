<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/util.php';

// -------------- SETUP -------------- //

$client = new \Github\Client();
$token = file_get_contents(__DIR__ . '/token.txt');
$client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);

$path = 'CONTRIBUTORS.md';
$pullRequestTitle = 'Add CONTRIBUTORS file';
$pullRequestMessage = 'Add CONTRIBUTORS file' . PHP_EOL . PHP_EOL . 'This PR is created automatically'
    . ' by [Matks PrestaShop Repositories Bulk Editor](https://github.com/matks/prestashop-repos-bulk-editor)';


$forkManager = new \Matks\PrestaShopRepoBulkEditor\ForkManager($client);
$branchManager = new \Matks\PrestaShopRepoBulkEditor\BranchManager($client);
$filesManager = new \Matks\PrestaShopRepoBulkEditor\RepositoryFilesManager($client);
$pullRequestManager = new \Matks\PrestaShopRepoBulkEditor\PullRequestsManager($client);

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

    // CHECK 3 check file does not already exist
    try {
        checkFileExists($filesManager, $repositoryName, $path, $baseBranch);
    } catch (\Matks\PrestaShopRepoBulkEditor\ContinueException $e) {
        continue;
    }

    // CHECK 4 check there is no PR already doing the add
    $pullRequestExists = $pullRequestManager->checkPRExistsWithName('prestashop', $repositoryName, $pullRequestTitle);
    if ($pullRequestExists) {
        echo '* PR already exists for ' . $repositoryName . PHP_EOL;
        continue;
    }

    // CHECK 5 the right branch exists on fork
    $branchAlreadyExists = $branchManager->checkBranchExistsWithName('matks', $repositoryName, $baseBranch);
    if (!$branchAlreadyExists) {
        echo '* Fork matks:' . $repositoryName . ' does not have branch ' . $baseBranch . PHP_EOL;
        continue;
    }

    // READY TO WORK
    try {
        $content = $filesManager->getContributorsListFileContent('prestashop', $repositoryName);
    } catch (\Matks\PrestaShopRepoBulkEditor\ContinueException $e) {
        echo '* Cannot compute contributor lists for ' . $repositoryName . PHP_EOL;
        continue;
    }

    $forkBranch = $baseBranch;
    createPRToCreateFile(
        $repositoryName,
        $baseBranch,
        $forkBranch,
        $pullRequestMessage,
        $pullRequestTitle,
        $filesManager,
        $path,
        $content,
        $pullRequestManager
    );
}
