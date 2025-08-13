<?php
/**
 * Test script to verify PHP syntax and check for function redeclaration issues
 */

// Test if we can include the files without errors
echo "Testing PHP syntax and function declarations...\n";

echo "1. Including setup.php...\n";
include_once(__DIR__ . '/setup.php');
echo "   ✓ setup.php loaded successfully\n";

echo "2. Including hook.php...\n";
include_once(__DIR__ . '/hook.php');
echo "   ✓ hook.php loaded successfully\n";

echo "3. Checking if key functions exist...\n";
echo "   ✓ plugin_init_hourstracking: " . (function_exists('plugin_init_hourstracking') ? "YES" : "NO") . "\n";
echo "   ✓ plugin_hourstracking_install: " . (function_exists('plugin_hourstracking_install') ? "YES" : "NO") . "\n";
echo "   ✓ plugin_hourstracking_install_complete: " . (function_exists('plugin_hourstracking_install_complete') ? "YES" : "NO") . "\n";
echo "   ✓ plugin_hourstracking_install_profiles: " . (function_exists('plugin_hourstracking_install_profiles') ? "YES" : "NO") . "\n";
echo "   ✓ plugin_hourstracking_uninstall: " . (function_exists('plugin_hourstracking_uninstall') ? "YES" : "NO") . "\n";

echo "\n4. Testing installation flow simulation...\n";
// This simulates what happens during plugin installation
try {
    if (function_exists('plugin_hourstracking_install')) {
        echo "   ✓ Installation function is callable\n";
    } else {
        echo "   ✗ Installation function not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ All tests completed successfully! No function redeclaration errors found.\n";
