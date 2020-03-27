<?php

require_once __DIR__ . '/vendor/autoload.php';

$client = new \Github\Client();
$token = file_get_contents(__DIR__.'/token.txt');
$client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);

$content = file_get_contents(__DIR__.'/template.txt');
$path = '.github/PULL_REQUEST_TEMPLATE.md';

$commitMessage = 'Add Pull Request template for github';
$branch = 'dev';
$committer = array('name' => 'matks', 'email' => 'mathieu.ferment@prestashop.com');

// $fileInfo = $client->api('repo')->contents()
//    ->create('matks', 'ps_facetedsearch', $path, $content, $commitMessage, $branch, $committer);

$message = 'This pull request a GitHub template for Pull Requests'.PHP_EOL.PHP_EOL.'This PR is created automatically.';

$pullRequest = $client->api('pull_request')->create('prestashop', 'ps_facetedsearch', array(
    'base'  => 'master',
    'head'  => 'matks:dev',
    'title' => 'Add GitHub PR template',
    'body'  => $message
));
