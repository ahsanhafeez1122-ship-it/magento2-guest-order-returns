<?php
declare(strict_types=1);

/**
 * The business rules (eligibility, quantities, lookup input, attempt limit) are plain PHP,
 * so their unit tests run without a Magento install. This autoloader maps the module namespace to this repo.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'AhsanHafeez\\GuestReturns\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
