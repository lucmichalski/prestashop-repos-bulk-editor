<?php

namespace Matks\PrestaShopRepoBulkEditor;

class LicenseHeaderFixerUtils
{
    private static $text = '/**
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

    public function __construct()
    {
        $this->license = str_replace('{currentYear}', date('Y'), self::getLicenseText());
        $this->license = str_replace('{licenseName}', 'Academic Free License 3.0 (AFL-3.0)', $this->license);
        $this->license = str_replace('{licenseLink}', 'https://opensource.org/licenses/AFL-3.0', $this->license);
    }

    /**
     * @return string
     */
    public static function getLicenseText()
    {
        return self::$text;
    }


    /**
     * @param string $file
     * @param string $startDelimiter
     * @param string $endDelimiter
     */
    public function addLicenseToFile($file, $startDelimiter = '\/', $endDelimiter = '\/')
    {
        $content = $file;
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

        return $content;
    }

    /**
     * @param $node
     * @param string $fileContent
     */
    public function addLicenseToNode($node, $fileContent)
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
    public function addLicenseToSmartyTemplate($fileContent)
    {
        return $this->addLicenseToFile($fileContent, '{', '}');
    }

    /**
     * @param string $fileContent
     */
    public function addLicenseToTwigTemplate($path, $fileContent)
    {
        if (strrpos($path, 'html.twig') !== false) {
            return $this->addLicenseToFile($fileContent, '{#', '#}');
        }

        return false;
    }

    /**
     * @param string $fileContent
     */
    public function addLicenseToHtmlFile($fileContent)
    {
        return $this->addLicenseToFile($fileContent, '<!--', '-->');
    }

    /**
     * @param string $fileContent
     *
     * @return bool
     */
    public function addLicenseToJsonFile($path, $fileContent)
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
