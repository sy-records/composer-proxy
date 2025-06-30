<?php

require 'vendor/autoload.php';

use Symfony\Component\Finder\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->path('/^[\w-]+\/composer\.lock/');

$url      = getenv('CNB_URL');
$login    = getenv('CNB_LOGIN');
$password = getenv('CNB_PASSWORD');

foreach ($finder as $value) {
    $json = json_decode(
        file_get_contents($value->getPathname()),
        true
    );

    $packages = $json['packages'] ?? [];
    foreach ($packages as $package) {
        $name       = $package['name'] ?? null;
        $version    = $package['version'] ?? null;
        $packageUrl = $package['dist']['url'] ?? null;

        if ($name && $version && $packageUrl) {
            echo "Processing {$name} {$version}\n";
            $dir     = dirname($name);
            $realDir = dirname($value->getRealPath()) . '/vendor/' . $name;
            @mkdir(__DIR__ . '/build/' . $dir, 0777, true);

            $build = __DIR__ . '/build/' . $name . '.zip';
            $cmd   = "cd {$realDir} && zip -r {$build} .";
            @exec($cmd);

            $output = [];
            $cmd = "cd build && curl -T {$name}.zip -u {$login}:{$password} {$url}/{$name}/{$version}";
            @exec($cmd, $output);
            echo implode("\n", $output) . "\n";
        }
    }
}
