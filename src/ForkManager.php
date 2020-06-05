<?php

namespace Matks\PrestaShopRepoBulkEditor;

use Github\Client;
use Github\Exception\RuntimeException;

class ForkManager
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
     * @param string $repositoryName
     * @param string $githubUsernameForker example: matks
     *
     * @return bool
     */
    public function checkForkExists(
        $repositoryName,
        $githubUsernameForker = 'matks')
    {
        try {
            $repo = $this->client->api('repo')->show($githubUsernameForker, $repositoryName);
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    /**
     * @param string $repositoryName
     * @param string $githubUsernameOriginal example: prestashop
     *
     * @return bool
     */
    public function createFork(
        $repositoryName,
        $githubUsernameOriginal = 'prestashop')
    {
        $fork = $this->client->api('repo')->forks()->create($githubUsernameOriginal, $repositoryName);

        return true;
    }
}
