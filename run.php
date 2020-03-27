<?php

require_once __DIR__ . '/vendor/autoload.php';

$client = new \Github\Client();
$token = file_get_contents(__DIR__.'/token.txt');
$client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);

$content = file_get_contents(__DIR__.'/template.txt');
$path = '.github/PULL_REQUEST_TEMPLATE.md';

// to iterate
$repositoryName = 'ps_shoppingcart';

$commitMessage = 'Add Pull Request template for github';
$branch = 'master';
// @todo: check I forked repository before
// @todo: check on my fork there is a master or dev branch
$committer = array('name' => 'matks', 'email' => 'mathieu.ferment@prestashop.com');

$fileInfo = $client->api('repo')->contents()
    ->create('matks', $repositoryName, $path, $content, $commitMessage, $branch, $committer);

$message = 'This pull request a GitHub template for Pull Requests'.PHP_EOL.PHP_EOL.'This PR is created automatically'
    . ' by [Matks PrestaShop Repositories Bulk Editor](https://github.com/matks/prestashop-repos-bulk-editor)';

$pullRequest = $client->api('pull_request')->create('prestashop', $repositoryName, array(
    'base'  => 'master',
    'head'  => 'matks:'.$branch,
    'title' => 'Add GitHub PR template',
    'body'  => $message
));
