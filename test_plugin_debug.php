<?php
/**
 * Debug script to test the ProductSaveButtonPlugin functionality
 */
require_once __DIR__ . '/../../../app/bootstrap.php';

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use MageOS\CatalogDataAI\Plugin\ProductSaveButtonPlugin;
use MageOS\CatalogDataAI\Model\Config;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Save;

// Initialize Magento
$bootstrap = Bootstrap::create(BP, $_SERVER);
$app = $bootstrap->createApplication('Magento\Framework\App\Http');
$objectManager = $app->getObjectManager();

try {
    // Set area
    $state = $objectManager->get(State::class);
    $state->setAreaCode('adminhtml');

    echo "Testing ProductSaveButtonPlugin Debug\n";
    echo "====================================\n\n";

    // Test 1: Check if Config class works and AI is enabled
    echo "1. Testing Config class...\n";
    $config = $objectManager->get(Config::class);
    $isEnabled = $config->isEnabled();
    $hasApiKey = !empty($config->getApiKey());
    echo "   AI Enrichment Enabled: " . ($isEnabled ? 'Yes' : 'No') . "\n";
    echo "   API Key configured: " . ($hasApiKey ? 'Yes' : 'No') . "\n";
    echo "   Should show options: " . ($isEnabled && $hasApiKey ? 'Yes' : 'No') . "\n\n";

    if (!$isEnabled) {
        echo "   ERROR: AI enrichment is not enabled. This is why options are not showing!\n\n";
    }
    if (!$hasApiKey) {
        echo "   ERROR: API key is not configured. This is why options are not showing!\n\n";
    }

    // Test 2: Try to instantiate the plugin
    echo "2. Testing Plugin instantiation...\n";
    $plugin = $objectManager->get(ProductSaveButtonPlugin::class);
    echo "   Plugin class loaded successfully\n\n";

    // Test 3: Test the plugin method directly
    echo "3. Testing plugin afterGetButtonData method...\n";
    $mockButtonData = [
        'label' => 'Save',
        'class' => 'save primary',
        'options' => [
            [
                'id_hard' => 'save_and_new',
                'label' => 'Save & New'
            ],
            [
                'id_hard' => 'save_and_close',
                'label' => 'Save & Close'
            ]
        ]
    ];

    echo "   Original options count: " . count($mockButtonData['options']) . "\n";

    // Call the plugin method
    $resultButtonData = $plugin->afterGetButtonData(null, $mockButtonData);
    echo "   Result options count: " . count($resultButtonData['options']) . "\n";

    echo "   All options:\n";
    foreach ($resultButtonData['options'] as $index => $option) {
        $label = $option['label'] ?? 'No label';
        $idHard = $option['id_hard'] ?? 'No id_hard';
        echo "     " . ($index + 1) . ". {$label} (id: {$idHard})\n";
    }

    // Check for AI options specifically
    $aiOptions = array_filter($resultButtonData['options'], function($option) {
        return isset($option['id_hard']) && strpos($option['id_hard'], 'ai_enrich') !== false;
    });

    echo "\n   AI Enrichment options found: " . count($aiOptions) . "\n";
    foreach ($aiOptions as $option) {
        echo "     - {$option['label']} (id: {$option['id_hard']})\n";
    }

    // Test 4: Check if the Save button class can be instantiated
    echo "\n4. Testing Save button class...\n";
    try {
        $saveButton = $objectManager->get(Save::class);
        echo "   Save button class loaded successfully\n";

        // Check if getOptions method exists
        if (method_exists($saveButton, 'getOptions')) {
            echo "   getOptions method exists on Save button\n";
        } else {
            echo "   ERROR: getOptions method does not exist on Save button!\n";
        }
    } catch (Exception $e) {
        echo "   ERROR: Could not instantiate Save button class: " . $e->getMessage() . "\n";
    }

    echo "\nTest completed!\n";
    if (count($aiOptions) > 0) {
        echo "✓ Plugin is working correctly - AI options are being added.\n";
        echo "The issue might be with DI compilation or plugin registration.\n";
    } else {
        echo "✗ Plugin is not adding AI options - there's an issue with the logic.\n";
    }

} catch (Exception $e) {
    echo "Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
