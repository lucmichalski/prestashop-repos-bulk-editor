<?php

$coreWeeklyGeneratorDirPath = __DIR__ . '/../../prestashop/core-weekly-generator/';
$currentWeekNumber = (int)(new \DateTime())->format("W")-1;

$coreWeeklyOutput = '';
echo "Generating Core Weekly from core-weekly-generator ..." . PHP_EOL;
exec(
    sprintf(
        'cd %s
        python3 ./core-weekly.py --week %s',
        $coreWeeklyGeneratorDirPath,
        $currentWeekNumber
    ),
    $coreWeeklyOutput
);

$generalMessageLine = '[write a nice message here, or remove the "General messages" section]';
$generalMessageLineNumber = null;
foreach ($coreWeeklyOutput as $number => $line) {
    if ($line === $generalMessageLine) {
        $generalMessageLineNumber = $number;
    }
}

if (!$number) {
    throw new \RuntimeException('Could not find general message line number');
}

echo "Generating Core Releases from prestashop-repos-bulk-editor ..." . PHP_EOL;
$getLatestReleasesOutput = '';
exec(
    'cd ' . __DIR__ . '
    php ./get-latest-releases.php',
    $getLatestReleasesOutput
);
$getLatestReleasesOutput = implode(PHP_EOL, $getLatestReleasesOutput);

$coreWeeklyOutput[$generalMessageLineNumber] = $generalMessageLine . PHP_EOL . PHP_EOL . $getLatestReleasesOutput;

$coreWeeklyOutput = implode(PHP_EOL, $coreWeeklyOutput);

file_put_contents(__DIR__ . '/core-weekly-' . $currentWeekNumber . '.md', $coreWeeklyOutput);

echo "Creating new branch to submit PR..." . PHP_EOL;
$buildRepositoryDirPath = __DIR__ . '/../../prestashop/prestashop.github.io/';

exec(
    sprintf(
        'cd %s
        git checkout master
        git checkout -b core-weekly-%s',
        $buildRepositoryDirPath,
        $currentWeekNumber
    )
);

$coreWeeklyFilename = sprintf(
    '%s-coreweekly-%s-%s.md',
    (new \DateTime())->format('Y-m-d'),
    $currentWeekNumber,
    (new \DateTime())->format('Y')
);

exec(
    sprintf(
        'cd %s
        touch news/_posts/core-weekly/%s',
        $buildRepositoryDirPath,
        $coreWeeklyFilename
    )
);

file_put_contents($buildRepositoryDirPath . 'news/_posts/core-weekly/' . $coreWeeklyFilename, $coreWeeklyOutput);

exec(sprintf(
        'cd %s
git add .
git commit -m "%s"',
        $buildRepositoryDirPath,
        'Add core weekly ' . $currentWeekNumber
    )
);

exec(sprintf(
        'cd %s
        git push --set-upstream origin core-weekly-%s',
        $buildRepositoryDirPath,
        $currentWeekNumber
    )
);

echo "Done!".PHP_EOL;
