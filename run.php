<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/util.php';

// -------------- SETUP -------------- //

$client = new \Github\Client();
$token = file_get_contents(__DIR__ . '/token.txt');
$client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);

$content = file_get_contents(__DIR__ . '/template.txt');
$path = '.github/PULL_REQUEST_TEMPLATE.md';
$pullRequestTitle = 'Add GitHub PR template';

$modulesToProcess = [
    'ps_facetedsearch',
    'welcome',
    'ps_shoppingcart',
    'ps_emailsubscription',
    'psgdpr'
];

foreach ($modulesToProcess as $moduleToProcess) {
    $repositoryName = $moduleToProcess;
    echo 'Analyzing repo prestashop:' . $repositoryName . PHP_EOL;

    // --------------  CHECKS --------------
    // CHECK 1 check fork exists
    try {
        $repo = $client->api('repo')->show('matks', $repositoryName);
    } catch (Github\Exception\RuntimeException $e) {
        echo '* Fork does not exist | matks:' . $repositoryName . PHP_EOL;
        echo '* Attempting to create fork ...' . PHP_EOL;

        $fork = $client->api('repo')->forks()->create('prestashop', $repositoryName);

        echo '* Fork successfully created | matks:' . $repositoryName . PHP_EOL;
    }

    $baseBranch = findBaseBranch($client, $repositoryName);
    echo '* Found base branch ' . $baseBranch . ' for prestashop:' . $repositoryName . PHP_EOL;

    // CHECK 2 is base branch identifiable
    if ($baseBranch === null) {
        echo '* Could not find base branch for repo prestashop :' . $repositoryName . PHP_EOL;
        continue;
    }

    // CHECK 3 check template file does not already exist
    $fileExists = $client->api('repo')->contents()
        ->exists('prestashop', $repositoryName, $path, 'refs/heads/' . $baseBranch);
    if ($fileExists) {
        echo '* Github template already exists for ' . $repositoryName . PHP_EOL;
        continue;
    }

    // CHECK 4 check there is no PR already doing the add
    $pullRequests = $client->api('pull_request')->all('prestashop', $repositoryName, ['state' => 'all']);
    foreach ($pullRequests as $pullRequest) {
        if ($pullRequest['title'] === $pullRequestTitle) {
            echo '* PR already exists for ' . $repositoryName . PHP_EOL;
            continue 2;
        }
    }

    if (!checkBranchExistsOnFork($client, $repositoryName, $baseBranch)) {
        echo '* Fork matks:' . $repositoryName . ' does not have branch ' . $baseBranch . PHP_EOL;
        continue;
    }

    echo sprintf(
        '\o/ Creating PR for repo %s %s => %s',
        $repositoryName,
        'matks:' . $baseBranch,
        'prestashop:' . $baseBranch
    );

    createPullRequest($client, $repositoryName, $path, $content, $baseBranch, $pullRequestTitle);
}



