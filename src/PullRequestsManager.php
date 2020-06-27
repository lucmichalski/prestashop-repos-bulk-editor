<?php

namespace Matks\PrestaShopRepoBulkEditor;

use Github\Client;

class PullRequestsManager
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param $repositoryName
     * @param $path
     * @param $baseBranch
     *
     * @return bool
     */
    public function checkPRExistsWithName($username, $repositoryName, $pullRequestTitle)
    {
        $pullRequests = $this->client->api('pull_request')->all($username, $repositoryName, ['state' => 'all']);

        foreach ($pullRequests as $pullRequest) {
            if ($pullRequest['title'] === $pullRequestTitle) {
                return true;
            }
        }

        return false;
    }

    public function createPR($username, $repositoryName, $branch, $pullRequestTitle, $message)
    {
        $pullRequest = $this->client->api('pull_request')->create('prestashop', $repositoryName, array(
            'base' => $branch,
            'head' => 'matks:' . $branch,
            'title' => $pullRequestTitle,
            'body' => $message
        ));

        return $pullRequest;
    }
}
