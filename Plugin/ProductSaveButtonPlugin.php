<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Plugin;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Save;
use MageOS\CatalogDataAI\Model\Config;

class ProductSaveButtonPlugin
{
    public function __construct(
        private readonly Config $config
    ) {}

    /**
     * Add AI enrichment options to the product save button dropdown
     *
     * @param Save $subject
     * @param array $result
     * @return array
     */
    public function afterGetButtonData(Save $subject, array $result): array
    {
        // Only add AI enrichment options if AI is enabled
        if (!$this->config->isEnabled()) {
            return $result;
        }

        // Initialize options array if it doesn't exist
        if (!isset($result['options'])) {
            $result['options'] = [];
        }

        // Add "Save & AI Enrich" option
        $result['options'][] = [
            'id_hard' => 'save_and_ai_enrich',
            'label' => __('Save & AI Enrich'),
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'product_form.product_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    [
                                        'back' => 'ai_enrich'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];

        // Add "Save & AI Enrich (Safe)" option
        $result['options'][] = [
            'id_hard' => 'save_and_ai_enrich_safe',
            'label' => __('Save & AI Enrich (Safe)'),
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'product_form.product_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    [
                                        'back' => 'ai_enrich_safe'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];

        return $result;
    }
}
