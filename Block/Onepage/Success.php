<?php

namespace IWD\Opc\Block\Onepage;

use Magento\Checkout\Block\Onepage\Success as CheckoutSuccess;
use IWD\Opc\Helper\Data as OpcHelper;
use IWD\Opc\Helper\Order as OpcOrder;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\Order\Config;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Registration;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\Order\Address\Validator;
use Magento\Sales\Api\OrderRepositoryInterface;

class Success extends CheckoutSuccess
{
    /**
     * @var Registration
     */
    public $registration;

    /**
     * @var AccountManagementInterface
     */
    public $accountManagement;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var Validator
     */
    public $addressValidator;

    /**
     * @var OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * @var OpcHelper
     */
    public $opcHelper;

    /**
     * @var OpcOrder
     */
    public $opcOrder;

    /**
     * Success constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param Config $orderConfig
     * @param HttpContext $httpContext
     * @param Registration $registration
     * @param AccountManagementInterface $accountManagement
     * @param CustomerSession $customerSession
     * @param Validator $addressValidator
     * @param OrderRepositoryInterface $orderRepository
     * @param OpcHelper $opcHelper
     * @param OpcOrder $opcOrder
     * @param array $data
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Config $orderConfig,
        HttpContext $httpContext,
        Registration $registration,
        AccountManagementInterface $accountManagement,
        CustomerSession $customerSession,
        Validator $addressValidator,
        OrderRepositoryInterface $orderRepository,
        OpcHelper $opcHelper,
        OpcOrder $opcOrder,
        array $data = []
    ) {
        $data['module_name'] = 'Magento_Checkout';
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
        $this->accountManagement = $accountManagement;
        $this->registration = $registration;
        $this->customerSession = $customerSession;
        $this->addressValidator = $addressValidator;
        $this->orderRepository = $orderRepository;
        $this->opcHelper = $opcHelper;
        $this->opcOrder = $opcOrder;
    }

    protected function _toHtml()
    {
        if ($this->opcHelper->isShowSuccessPage() &&
            $this->opcHelper->isEnable() && $this->opcHelper->isModuleOutputEnabled('IWD_Opc')) {
            $this->setTemplate('IWD_Opc::success/success.phtml');
            if ($this->getNameInLayout() === 'checkout.success.print.button') {
                return '';
            }
        }

        return parent::_toHtml();
    }

    public function getCreateAccountUrl()
    {
        return $this->getUrl('checkout/account/create');
    }

    public function getCreateAccountWithPasswordUrl()
    {
        return $this->getUrl('onepage/account/createwithpassword');
    }

    public function getCustomerAccountUrl()
    {
        return $this->getUrl('customer/account');
    }

    public function getLogInUrl()
    {
        return $this->getUrl('customer/account/login');
    }

    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    public function getEmailAddress()
    {
        return $this->_checkoutSession->getLastRealOrder()->getCustomerEmail();
    }

    public function isShowRegistrationForm()
    {
        if ($this->customerSession->getIsIWDcreateAccount()) {
            $this->customerSession->unsIsIWDcreateAccount();
            $this->customerSession->setIWDAccountPassword(true);

            return true;
        }

        if ($this->isCustomerLoggedIn()
            || !$this->registration->isAllowed()
            || !$this->accountManagement->isEmailAvailable($this->getEmailAddress())
            || !$this->validateAddresses()
        ) {
            return false;
        }

        return true;
    }

    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    public function validateAddresses()
    {
        $addresses = $this->getOrder()->getAddresses();
        foreach ($addresses as $address) {
            $result = $this->addressValidator->validateForCustomer($address);
            if (is_array($result) && !empty($result)) {
                return false;
            }
        }

        return true;
    }

    public function getOrder() {
        return $this->orderRepository->get($this->_checkoutSession->getLastOrderId());
    }

    public function getOrderDetailsForGa4() {
        return $this->opcOrder->getOrderDetailsForGa4($this->getOrder());
    }
}
