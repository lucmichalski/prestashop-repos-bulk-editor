<?php

require_once __DIR__ . '/vendor/autoload.php';

$client = new \Github\Client();
$token = file_get_contents(__DIR__ . '/token.txt');
$client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);

$content = file_get_contents(__DIR__ . '/template.txt');
$path = '.github/PULL_REQUEST_TEMPLATE.md';

// to iterate
$repositoryName = 'welcome';
$pullRequestTitle = 'Add GitHub PR template';

// check fork exists
try {
    $repo = $client->api('repo')->show('matks', $repositoryName);
} catch (Github\Exception\RuntimeException $e) {
    echo 'Fork does not exist: ' . $repositoryName . ':' . $repositoryName . PHP_EOL;
    die();
}
// @todo: create fork if it does not exist

// find base branch on target repository
$references = $client->api('gitData')->references()->branches('prestashop', $repositoryName);
$branches = [];
foreach ($references as $info) {
    $branches[str_replace('refs/heads/', '', $info['ref'])] = str_replace('refs/heads/', '', $info['ref']);
}
$baseBranch = null;
if (array_key_exists('dev', $branches)) {
    $baseBranch = 'dev';
}
if (array_key_exists('develop', $branches)) {
    $baseBranch = 'develop';
}
if (array_key_exists('master', $branches)) {
    $baseBranch = 'master';
}

if ($baseBranch === null) {
    echo 'Could not find base branch for repo prestashop :' . $repositoryName . PHP_EOL;
    die();
}

// check template file does not already exist
$fileExists = $client->api('repo')->contents()
    ->exists('prestashop', $repositoryName, $path, 'refs/heads/'.$baseBranch);
if ($fileExists) {
    echo 'Github template already exists for ' . $repositoryName . PHP_EOL;
    die();
}

// check there is no PR already doing the add
$pullRequests = $client->api('pull_request')->all('prestashop', $repositoryName, ['state' => 'all']);
foreach ($pullRequests as $pullRequest) {
    if ($pullRequest['title'] === $pullRequestTitle) {
        echo 'PR already exists for ' . $repositoryName . PHP_EOL;
        die();
    }
}

// check branch exists on fork
$references = $client->api('gitData')->references()->branches('matks', $repositoryName);
foreach ($references as $info) {
    $branches[str_replace('refs/heads/', '', $info['ref'])] = str_replace('refs/heads/', '', $info['ref']);
}
if (!array_key_exists($baseBranch, $branches)) {
    echo 'Fork matks:' . $repositoryName . ' does not have branch ' . $baseBranch . PHP_EOL;
    die();
}

echo sprintf(
    'Creating PR for repo %s %s => %s',
    $repositoryName,
    'matks:' . $baseBranch,
    'prestashop:' . $baseBranch
);

die("hahaha");

$commitMessage = 'Add Pull Request template for github';
$committer = array('name' => 'matks', 'email' => 'mathieu.ferment@prestashop.com');

$fileInfo = $client->api('repo')->contents()
    ->create('matks', $repositoryName, $path, $content, $commitMessage, $baseBranch, $committer);

$message = 'This pull request a GitHub template for Pull Requests' . PHP_EOL . PHP_EOL . 'This PR is created automatically'
    . ' by [Matks PrestaShop Repositories Bulk Editor](https://github.com/matks/prestashop-repos-bulk-editor)';

$pullRequest = $client->api('pull_request')->create('prestashop', $repositoryName, array(
    'base' => $baseBranch,
    'head' => 'matks:' . $baseBranch,
    'title' => $pullRequestTitle,
    'body' => $message
));
