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

    /**
     * @param string $username
     * @param string $repositoryName
     *
     * @return string
     *
     * @throws ContinueException
     */
    public function getContributorsListFileContent($username, $repositoryName)
    {
        $githubData = $this->client->api('repo')->contributors($username, $repositoryName, true);

        $list = [];
        foreach ($githubData as $githubContrib) {
            if ($githubContrib['type'] === 'User') {
                $list[] = $githubContrib['login'];
            } else {
                $list[] = $githubContrib['name'];
            }
        }

        if (empty($list)) {
            throw new ContinueException();
        }
        sort($list);

        $fileContent = $this->renderFile($list);

        return $fileContent;
    }

    /**
     * @param array $list
     * @return string
     */
    private function renderFile($list)
    {
        return sprintf(
            'GitHub contributors:' . PHP_EOL .
            '--------------------------------' . PHP_EOL . '%s' . PHP_EOL,
            ' - ' . implode(PHP_EOL . ' - ', $list)
        );
    }
}
