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

    /**
     * @param $username
     * @param $repositoryName
     * @param $baseBranch
     *
     * @return bool
     */
    public function checkBranchExistsWithName($username, $repositoryName, $branch)
    {
        $references = $this->client->api('gitData')->references()->branches($username, $repositoryName);
        $branches = [];

        foreach ($references as $info) {
            $branches[str_replace('refs/heads/', '', $info['ref'])] = str_replace('refs/heads/', '', $info['ref']);
        }

        return array_key_exists($branch, $branches);
    }

    /**
     * @param string $username
     * @param string $repositoryName
     * @param string $branchName
     *
     * @return bool
     */
    public function deleteBranch($username, $repositoryName, $branchName)
    {
        $result = $this->client->api('gitData')->references()->remove(
            $username,
            $repositoryName,
            'heads/' . $branchName
        );
    }

    /**
     * @param string $username
     * @param string $repositoryName
     * @param string $branchName
     *
     * @return bool
     */
    public function pullUpstreamBranchIntoFork($username, $repositoryName, $branchName)
    {
        $upstreamReferenceData = $this->client->api('gitData')->references()->show(
            'prestashop',
            $repositoryName,
            'heads/' . $branchName
        );

        $sha = $upstreamReferenceData['object']['sha'];

        $forkReferenceDataInput = [
            'ref' => 'refs/heads/' . $branchName,
            'sha' => $sha
        ];

        $forkReferenceData = $this->client->api('gitData')->references()->create(
            $username,
            $repositoryName,
            $forkReferenceDataInput
        );

        return true;
    }

    /**
     * @param string $username
     * @param string $repositoryName
     * @param string $sourceBranchName
     * @param string $newBranchName
     *
     * @return bool
     *
     * @throws \Github\Exception\MissingArgumentException
     */
    public function createBranchFrom($username, $repositoryName, $sourceBranchName, $newBranchName)
    {
        $sourceBranchData = $this->client->api('gitData')->references()->show($username, $repositoryName, 'heads/' . $sourceBranchName);
        $sha = $sourceBranchData['object']['sha'];

        $newBranchInputData = [
            'ref' => 'refs/heads/' . $newBranchName,
            'sha' => $sha
        ];

        $newBranchData = $this->client->api('gitData')->references()->create(
            $username,
            $repositoryName,
            $newBranchInputData
        );

        return !empty($newBranchData);
    }
}
