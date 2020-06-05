<?php

namespace Matks\PrestaShopRepoBulkEditor;

use Github\Client;

class RepositoryFilesManager
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
    public function checkFileExists($username, $repositoryName, $path, $baseBranch)
    {
        $fileExists = $this->client->api('repo')->contents()
            ->exists($username, $repositoryName, $path, 'refs/heads/' . $baseBranch);

        return $fileExists;
    }

    /**
     * @param $username
     * @param $repositoryName
     * @param $path
     * @param $content
     * @param $commitMessage
     * @param $branch
     *
     * @throws \Github\Exception\MissingArgumentException
     */
    public function createFileOnRepo($username, $repositoryName, $path, $content, $commitMessage, $branch)
    {
        $committer = array('name' => 'matks', 'email' => 'mathieu.ferment@prestashop.com');

        $fileInfo = $this->client->api('repo')->contents()
            ->create($username, $repositoryName, $path, $content, $commitMessage, $branch, $committer);
    }
}
