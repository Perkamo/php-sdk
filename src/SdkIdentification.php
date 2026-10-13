<?php

declare(strict_types=1);

namespace Perkamo;

use Composer\InstalledVersions;
use InvalidArgumentException;

final class SdkIdentification
{
    private const SEMVER = '/^(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?(?:\+[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*)?$/';
    private const PACKAGE = '/^(?:@perkamo|perkamo)\/[a-z0-9-]+$/';

    public static function forPackage(string $package, string $fallbackVersion): string
    {
        $version = $fallbackVersion;
        $rootPackage = class_exists(InstalledVersions::class)
            ? InstalledVersions::getRootPackage()
            : [];
        $isSourceCheckout = ($rootPackage['name'] ?? null) === $package;
        if (
            class_exists(InstalledVersions::class)
            && !$isSourceCheckout
            && InstalledVersions::isInstalled($package)
        ) {
            $prettyVersion = InstalledVersions::getPrettyVersion($package);
            if (is_string($prettyVersion)) {
                $candidate = ltrim(trim($prettyVersion), 'v');
                if (preg_match(self::SEMVER, $candidate) === 1) {
                    $version = $candidate;
                }
            }
        }

        return self::validate($package . '@' . $version);
    }

    public static function validate(string $identification): string
    {
        $separator = strrpos($identification, '@');
        $package = $separator === false ? '' : substr($identification, 0, $separator);
        $version = $separator === false ? '' : substr($identification, $separator + 1);
        if (
            strlen($identification) > 128
            || preg_match(self::PACKAGE, $package) !== 1
            || preg_match(self::SEMVER, $version) !== 1
        ) {
            throw new InvalidArgumentException('Invalid Perkamo SDK identification');
        }

        return $identification;
    }
}
