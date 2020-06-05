<?php


namespace Matks\PrestaShopRepoBulkEditor;

use Github\Client;

class BranchManager
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
     * @return string|null null if failed to find base branch
     */
    public function findRepositoryBaseBranch($repositoryName)
    {
        $references = $this->client->api('gitData')->references()->branches('prestashop', $repositoryName);

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
}
