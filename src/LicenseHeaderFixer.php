<?php

namespace Matks\PrestaShopRepoBulkEditor;

use PhpParser\ParserFactory;

class LicenseHeaderFixer
{
    private $text = '/**
 * 2007-{currentYear} PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the {licenseName}
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * {licenseLink}
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-{currentYear} PrestaShop SA and Contributors
 * @license   {licenseLink} {licenseName}
 * International Registered Trademark & Property of PrestaShop SA
 */';

    /**
     * @var string
     */
    private $license;

    private $blacklist = [
        // versioning folders
        '.git',
        '.github',
        '.composer',
        // admin folders
        'admin-dev/filemanager',
        'admin-dev/themes/default/public/',
        'admin-dev/themes/new-theme/public/',
        // js dependencies
        'js/tiny_mce',
        'js/jquery',
        'js/cropper',
        // mails folder
        'mails/themes/classic/',
        'mails/themes/modern/',
        // tools dependencies
        'tools/htmlpurifier',
        // dependencies
        'vendor',
        'node_modules',
        // themes assets
        'themes/classic/assets/',
        'themes/starterTheme/assets/',
        // tests folders
        'tests/Resources/modules/',
        'tests/Resources/themes/',
        'tests/Resources/translations/',
        'tests/resources/ModulesOverrideInstallUninstallTest/',
        'tests-legacy/PrestaShopBundle/Twig/Fixtures/',
        'tests-legacy/resources/',
        'tests/E2E/',
        'tests/Unit/Resources/assets/',
        'tests/puppeteer/',
        'composer.json',
        'package.json',
        'admin-dev/themes/default/css/font.css',
        'admin-dev/themes/new-theme/package.json',
        'tools/build/Library/InstallUnpacker/content/js-runner.js',
        'themes/classic/_dev/package.json',
        'tools/build/composer.json',
    ];

    /**
     * @var \Github\Client()
     */
    private $githubClient;

    /**
     * @param \Github\Client $githubClient
     */
    public function __construct(\Github\Client $githubClient)
    {
        $this->githubClient = $githubClient;
    }

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
            $sha = $scanItem['sha'];

            if ($type === 'dir') {
                $this->scanDir($repositoryName, $baseBranch, $path, $recursionLevel + 1);
            } else {
                $this->scanFile($repositoryName, $baseBranch, $sha, $path);
            }
        }
    }

    public function scanFile($repositoryName, $baseBranch, $sha, $path = null)
    {
        $fileContent = $this->githubClient->api('repo')->contents()
            ->download('matks', $repositoryName, $path);

        $result = $this->fixFileContent($path, $fileContent);

        if (false === $result) {
            echo '    -> File ' . $path . ' could not be fixed' . PHP_EOL;
            return;
        }

        if ($fileContent === $result) {
            echo '    -> File ' . $path . ' is valid, nothing to fix' . PHP_EOL;
            return;
        }

        $commitMessage = 'Update license header file';
        $committer = array('name' => 'matks', 'email' => 'mathieu.ferment@prestashop.com');

        $fileInfo = $this->githubClient->api('repo')->contents()
            ->update(
                'matks',
                $repositoryName,
                $path,
                $result,
                $commitMessage,
                $sha,
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

        $this->text = str_replace('{currentYear}', date('Y'), $this->text);

        $this->license = $this->text;
        $this->license = str_replace('{licenseName}', 'Academic Free License 3.0 (AFL-3.0)', $this->license);
        $this->license = str_replace('{licenseLink}', 'https://opensource.org/licenses/AFL-3.0', $this->license);

        if (in_array($path, $this->blacklist)) {
            echo '  ... blacklisted' . PHP_EOL;
            return false;
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
            echo '  ... blacklisted because extension is ' . $path_parts['extension'] . PHP_EOL;
            return false;
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        switch ($path_parts['extension']) {
            case 'php':
                try {
                    $nodes = $parser->parse($fileContentAsString);
                    if (count($nodes)) {
                        return $this->addLicenseToNode($nodes[0], $fileContentAsString);
                    }
                } catch (\PhpParser\Error $exception) {
                    echo 'Syntax error on file ' . $path . ' . Continue ...' . PHP_EOL;
                    return false;
                }

                break;
            case 'js':
            case 'css':
                return $this->addLicenseToFile($fileContentAsString);

                break;
            case 'tpl':
                return $this->addLicenseToSmartyTemplate($fileContentAsString);

                break;
            case 'twig':
                return $this->addLicenseToTwigTemplate($path, $fileContentAsString);

                break;
            case 'json':
                return $this->addLicenseToJsonFile($path, $fileContentAsString);

                break;
            case 'vue':
                return $this->addLicenseToHtmlFile($fileContentAsString);

                break;
        }

        return false;
    }

    /**
     * @param SplFileInfo $file
     * @param string $startDelimiter
     * @param string $endDelimiter
     */
    private function addLicenseToFile($file, $startDelimiter = '\/', $endDelimiter = '\/')
    {
        $content = $file->getContents();
        // Regular expression found thanks to Stephen Ostermiller's Blog. http://blog.ostermiller.org/find-comment
        $regex = '%' . $startDelimiter . '\*([^*]|[\r\n]|(\*+([^*' . $endDelimiter . ']|[\r\n])))*\*+' . $endDelimiter . '%';
        $matches = [];
        $text = $this->license;
        if ($startDelimiter != '\/') {
            $text = $startDelimiter . ltrim($text, '/');
        }
        if ($endDelimiter != '\/') {
            $text = rtrim($text, '/') . $endDelimiter;
        }

        // Try to find an existing license
        preg_match($regex, $content, $matches);

        if (count($matches)) {
            // Found - Replace it if prestashop one
            foreach ($matches as $match) {
                if (stripos($match, 'prestashop') !== false) {
                    $content = str_replace($match, $text, $content);
                }
            }
        } else {
            // Not found - Add it at the beginning of the file
            $content = $text . "\n" . $content;
        }

        file_put_contents($file->getRelativePathname(), $content);
    }

    /**
     * @param $node
     * @param string $fileContent
     */
    private function addLicenseToNode($node, $fileContent)
    {
        if (!$node->hasAttribute('comments')) {
            $needle = '<?php';
            $replace = "<?php\n" . $this->license . "\n";
            $haystack = $fileContent;

            $pos = strpos($haystack, $needle);
            // Important, if the <?php is in the middle of the file, continue
            if ($pos === 0) {
                $newstring = substr_replace($haystack, $replace, $pos, strlen($needle));
                return $newstring;
            }

            return false;
        }

        $comments = $node->getAttribute('comments');
        foreach ($comments as $comment) {
            if ($comment instanceof \PhpParser\Comment
                && strpos($comment->getText(), 'prestashop') !== false) {
                return str_replace($comment->getText(), $this->license, $fileContent);
            }
        }

        return false;
    }

    /**
     * @param string $fileContent
     */
    private function addLicenseToSmartyTemplate($fileContent)
    {
        return $this->addLicenseToFile($fileContent, '{', '}');
    }

    /**
     * @param string $fileContent
     */
    private function addLicenseToTwigTemplate($path, $fileContent)
    {
        if (strrpos($path, 'html.twig') !== false) {
            return $this->addLicenseToFile($fileContent, '{#', '#}');
        }

        return false;
    }

    /**
     * @param string $fileContent
     */
    private function addLicenseToHtmlFile($fileContent)
    {
        return $this->addLicenseToFile($fileContent, '<!--', '-->');
    }

    /**
     * @param string $fileContent
     *
     * @return bool
     */
    private function addLicenseToJsonFile($path, $fileContent)
    {
        if (!in_array($path, ['composer.json', 'package.json'])) {
            return false;
        }

        $content = (array)json_decode($fileContent);
        $content['author'] = 'PrestaShop';
        $content['license'] = 'AFL-3.0';

        return json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

}
