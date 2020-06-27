<?php

namespace Matks\PrestaShopRepoBulkEditor;

use PhpParser\ParserFactory;

class LicenseHeaderFixer
{


    /**
     * @var string
     */
    private $license;

    private $blacklist = [
        // versioning folders
        '.git',
        '.github',
        '.composer',
        // dependencies
        'vendor',
        'node_modules',
        'composer.json',
        'package.json',
    ];

    /**
     * @var \Github\Client()
     */
    private $githubClient;

    /** @var LicenseHeaderFixerUtils */
    private $utils;

    /**
     * @param \Github\Client $githubClient
     */
    public function __construct(\Github\Client $githubClient)
    {
        $this->githubClient = $githubClient;
        $this->utils = new LicenseHeaderFixerUtils();
    }

    /**
     * @param $repositoryName
     * @param $baseBranch
     * @param string g$path
     * @param int $recursionLevel
     */
    public function scanDir($repositoryName, $baseBranch, $path = null, $recursionLevel = 0)
    {
        if ($recursionLevel > 0) {
            //echo 'RC level: ' . $recursionLevel . PHP_EOL;
        }

        echo '* Scanning directory ' . $path . PHP_EOL;
        $repoScan = $this->githubClient->api('repo')->contents()
            ->show('matks', $repositoryName, $path);

        foreach ($repoScan as $scanItem) {
            $type = $scanItem['type'];
            $path = $scanItem['path'];

            if ($type === 'dir') {
                $this->scanDir($repositoryName, $baseBranch, $path, $recursionLevel + 1);
            } else {
                $this->scanAndFixFile($repositoryName, $baseBranch, $path);
            }
        }
    }

    /**
     * @param $repositoryName
     * @param $baseBranch
     * @param string $path
     *
     * @throws \Github\Exception\ErrorException
     * @throws \Github\Exception\MissingArgumentException
     */
    public function scanAndFixFile($repositoryName, $baseBranch, $path = null)
    {
        $fileContent = $this->githubClient->api('repo')->contents()
            ->download('matks', $repositoryName, $path, $baseBranch);
        $scan = $this->githubClient->api('repo')->contents()
            ->show('matks', $repositoryName, $path, $baseBranch);

        /** @var LicenseHeaderFixResult $result */
        $result = $this->fixFileContent($path, $fileContent);

        if ($result->type === LicenseHeaderFixResult::IGNORED_FILE) {
            echo '    -> File ' . $path . ' ignored: ' . $result->message . PHP_EOL;
            return;
        }
        if ($result->type === LicenseHeaderFixResult::FAILED_TO_FIX) {
            echo '    -> File ' . $path . ' fix failed: ' . $result->message . PHP_EOL;
            return;
        }

        if ($fileContent === $result) {
            echo '    -> File ' . $path . ' is valid, nothing to fix' . PHP_EOL;
            return;
        }

        echo '    -> File ' . $path . ' has been fixed' . PHP_EOL;

        $commitMessage = 'Update license header file ' . $path;
        $committer = array('name' => 'matks', 'email' => 'mathieu.ferment@prestashop.com');

        $fileInfo = $this->githubClient->api('repo')->contents()
            ->update(
                'matks',
                $repositoryName,
                $path,
                $result->fixFiledContent,
                $commitMessage,
                $scan['sha'],
                $baseBranch,
                $committer
            );
    }


    /**
     * @param $path
     * @param $fileContentAsString
     *
     * @return string|bool fixed file content or false
     */
    protected function fixFileContent($path, $fileContentAsString)
    {
        echo '  - Processing file ' . $path . PHP_EOL;

        if (in_array($path, $this->blacklist)) {
            return LicenseHeaderFixResult::createBlacklistedResult('blacklisted file');
        }

        $path_parts = pathinfo($path);
        $extensions = [
            'php',
            'js',
            'css',
            'tpl',
            'html.twig',
            'json',
            'vue',
        ];

        if (!in_array($path_parts['extension'], $extensions)) {
            return LicenseHeaderFixResult::createBlacklistedResult(
                'blacklisted extension ' . $path_parts['extension']
            );
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        switch ($path_parts['extension']) {
            case 'php':
                try {
                    $nodes = $parser->parse($fileContentAsString);
                    if (count($nodes)) {
                        $content = $this->utils->addLicenseToNode($nodes[0], $fileContentAsString);

                        if (!$content) {
                            return LicenseHeaderFixResult::createFailedToFixResult('Empty file ' . $path);
                        }

                        return LicenseHeaderFixResult::createSuccessResult($content);
                    }
                } catch (\PhpParser\Error $exception) {
                    return LicenseHeaderFixResult::createFailedToFixResult('Syntax error on file ' . $path);
                }

                break;
            case 'js':
            case 'css':
                return LicenseHeaderFixResult::createSuccessResult(
                    $this->utils->addLicenseToFile($fileContentAsString)
                );

                break;
            case 'tpl':
                return LicenseHeaderFixResult::createSuccessResult(
                    $this->utils->addLicenseToSmartyTemplate($fileContentAsString)
                );

                break;
            case 'twig':
                return LicenseHeaderFixResult::createSuccessResult(
                    $this->utils->addLicenseToTwigTemplate($path, $fileContentAsString)
                );

                break;
            case 'json':
                return LicenseHeaderFixResult::createSuccessResult(
                    $this->utils->addLicenseToJsonFile($path, $fileContentAsString)
                );

                break;
            case 'vue':
                return LicenseHeaderFixResult::createSuccessResult(
                    $this->utils->addLicenseToHtmlFile($fileContentAsString)
                );

                break;
        }

        return false;
    }
}
