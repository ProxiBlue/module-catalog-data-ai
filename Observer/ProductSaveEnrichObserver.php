<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use MageOS\CatalogDataAI\Model\Config;
use MageOS\CatalogDataAI\Model\Product\Publisher;

class ProductSaveEnrichObserver implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Publisher $publisher,
        private readonly ManagerInterface $messageManager
    ) {}

    /**
     * Handle product save event and trigger AI enrichment if requested
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Catalog\Controller\Adminhtml\Product\Save $controller */
        $controller = $observer->getData('controller');

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getData('product');

        if (!$controller || !$product) {
            return;
        }

        // Get the 'back' parameter to check if AI enrichment was requested
        $redirectBack = $controller->getRequest()->getParam('back', false);

        // Check if AI enrichment is enabled
        if (!$this->config->isEnabled()) {
            if ($redirectBack === 'ai_enrich' || $redirectBack === 'ai_enrich_safe') {
                $this->messageManager->addErrorMessage(
                    __('AI enrichment is disabled. Please enable it in the configuration.')
                );
            }
            return;
        }

        // Handle AI enrichment requests
        if ($redirectBack === 'ai_enrich') {
            $this->enrichProduct($product, true);
        } elseif ($redirectBack === 'ai_enrich_safe') {
            $this->enrichProduct($product, false);
        }
    }

    /**
     * Enrich product via publisher
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param bool $overwrite
     * @return void
     */
    private function enrichProduct(\Magento\Catalog\Model\Product $product, bool $overwrite): void
    {
        try {
            $this->publisher->execute($product->getId(), $overwrite);

            $mode = $overwrite ? 'enrichment' : 'safe enrichment';
            $this->messageManager->addSuccessMessage(
                __('Product has been scheduled for AI %1.', $mode)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Failed to schedule product for AI enrichment: %1', $e->getMessage())
            );
        }
    }
}
