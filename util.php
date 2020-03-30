<?php

/**
 * Find base branch on target repository
 *
 * @param $client
 * @param $repositoryName
 *
 * @return string
 */
function findBaseBranch($client, $repositoryName)
{
    $references = $client->api('gitData')->references()->branches('prestashop', $repositoryName);
    $branches = [];
    foreach ($references as $info) {
        $branches[str_replace('refs/heads/', '', $info['ref'])] = str_replace('refs/heads/', '', $info['ref']);
    }

    if (array_key_exists('dev', $branches)) {
        return 'dev';
    }
    if (array_key_exists('develop', $branches)) {
        return 'develop';
    }
    if (array_key_exists('master', $branches)) {
        return 'master';
    }

    return null;
}

/**
 * @param $client
 * @param $repositoryName
 * @param $baseBranch
 *
 * @return bool
 */
function checkBranchExistsOnFork($client, $repositoryName, $baseBranch)
{
    $references = $client->api('gitData')->references()->branches('matks', $repositoryName);
    $branches = [];

    foreach ($references as $info) {
        $branches[str_replace('refs/heads/', '', $info['ref'])] = str_replace('refs/heads/', '', $info['ref']);
    }

    return array_key_exists($baseBranch, $branches);
}

/**
 * @param $client
 * @param $repositoryName
 * @param $path
 * @param $content
 * @param $baseBranch
 * @param $pullRequestTitle
 */
function createPullRequest($client, $repositoryName, $path, $content, $baseBranch, $pullRequestTitle)
{
    $commitMessage = 'Add Pull Request template for github';
    $committer = array('name' => 'matks', 'email' => 'mathieu.ferment@prestashop.com');

    $fileInfo = $client->api('repo')->contents()
        ->create('matks', $repositoryName, $path, $content, $commitMessage, $baseBranch, $committer);

    $message = 'This pull request a GitHub template for Pull Requests' . PHP_EOL . PHP_EOL . 'This PR is created automatically'
        . ' by [Matks PrestaShop Repositories Bulk Editor](https://github.com/matks/prestashop-repos-bulk-editor)';

    // CHECK 1 check fork exists
    try {
        $pullRequest = $client->api('pull_request')->create('prestashop', $repositoryName, array(
            'base' => $baseBranch,
            'head' => 'matks:' . $baseBranch,
            'title' => $pullRequestTitle,
            'body' => $message
        ));
    } catch (Github\Exception\RuntimeException $e) {
        echo '!!! Failed to create PR for prestashop:' . $repositoryName . PHP_EOL;
    }

}
