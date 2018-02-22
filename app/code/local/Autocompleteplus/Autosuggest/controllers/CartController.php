<?php
/**
 * CartController.php
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category Mage
 *
 * @package   Instantsearchplus
 * @author    Fast Simon <info@instantsearchplus.com>
 * @copyright 2018 Fast Simon (http://www.instantsearchplus.com)
 * @license   Open Software License (OSL 3.0)*
 * @link      http://opensource.org/licenses/osl-3.0.php
 */

require_once Mage::getModuleDir('controllers', 'Mage_Checkout').DS.'CartController.php';

/**
 * Class Autocompleteplus_Autosuggest_CartController
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category Mage
 *
 * @package   Instantsearchplus
 * @author    Fast Simon <info@instantsearchplus.com>
 * @copyright Fast Simon (http://www.instantsearchplus.com)
 * @license   Open Software License (OSL 3.0)*
 * @link      http://opensource.org/licenses/osl-3.0.php
 */
class Autocompleteplus_Autosuggest_CartController extends Mage_Checkout_CartController
{

    /**
     * Add product to shopping cart action
     *
     * @return Mage_Core_Controller_Varien_Action
     * @throws Exception
     */
    public function addAction()
    {
        if (!$this->_validateFormKey()) {
            $this->_quitOnError('Form keys do not match!');
            return;
        }
        $cart   = $this->_getCart();
        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                $this->_quitOnError('Product does not exists!');
                return;
            }

            $cart->addProduct($product, $params);
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();

            $this->_getSession()->setCartWasUpdated(true);

            /**
             * @todo remove wishlist observer processAddToCart
             */
            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );
            $cartBlock = $this->getLayout()->createBlock('checkout/cart_minicart')
                ->setTemplate('checkout/cart/minicart.phtml');
            if (!$cart->getQuote()->getHasError()) {
                $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                $response = $this->getResponse();
                $responseData = [
                    'success' => true,
                    'message' => $message,
                    'content' => $cartBlock->toHtml()
                ];
                $response->clearHeaders();
                $response->setHeader('Content-type', 'text/json');
                $response->setBody(json_encode($responseData));
                return;
            }
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->_getSession()->addError(Mage::helper('core')->escapeHtml($message));
                }
            }

            $url = $this->_getSession()->getRedirectUrl(true);
            if (!$url) {
                $url = Mage::helper('checkout/cart')->getCartUrl();
            }
            $response = $this->getResponse();
            $responseData = [
                'success' => false,
                'url' => $url
            ];
            $response->clearHeaders();
            $response->setHeader('Content-type', 'text/json');
            $response->setBody(json_encode($responseData));
            return;
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_quitOnError('Cannot add the item to shopping cart!');
        }
    }

    /**
     * @param $response
     */
    private function _quitOnError($message)
    {
        $response = $this->getResponse();
        $responseData = [
            'success' => false,
            'message' => $message
        ];
        $response->clearHeaders();
        $response->setHeader('Content-type', 'text/json');
        $response->setBody(json_encode($responseData));
        return;
    }
}