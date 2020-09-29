<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/util.php';

// -------------- SETUP -------------- //

$client = new \Github\Client();
$token = file_get_contents(__DIR__ . '/token.txt');
$client->authenticate($token, null, Github\Client::AUTH_HTTP_TOKEN);

$organizationApi = $client->api('organization');

$paginator = new Github\ResultPager($client);
$parameters = array('prestashop');
$result = $paginator->fetchAll($organizationApi, 'repositories', $parameters);

$releasesData = [];
$filterOnlyLastWeek = true;
$oneWeekAgo = new DateTime();
$oneWeekAgo->modify('- 7 day');

$i = 0;

foreach ($result as $repository) {
    $repoName = $repository['name'];

    try {
        $release = $client->api('repo')->releases()->latest(
            'prestashop', $repoName
        );

        $publishedAt = \DateTime::createFromFormat(
            \DateTime::RFC3339,
            $release['published_at']
        );

        if ($filterOnlyLastWeek) {
            if ($publishedAt < $oneWeekAgo) {
                continue;
            }
        }

        $releasesData[$repoName] = [
            'repository' => $repoName,
            'url' => $release['html_url'],
            'version' => $release['name'],
            'published_at' => $publishedAt
        ];

    } catch (\Github\Exception\RuntimeException $e) {
        if ($e->getCode() !== 404) {
            throw $e;
        }
    }

    $i++;
}

$title = '## Releases'.PHP_EOL.PHP_EOL;
foreach ($releasesData as $release) {
    $title.= sprintf(
        '* [%s](%s): [%s](%s)',
        $release['repository'],
        'https://github.com/PrestaShop/'.$release['repository'],
        $release['version'],
        $release['url']
    );
    $title.= PHP_EOL;
}

echo $title;

