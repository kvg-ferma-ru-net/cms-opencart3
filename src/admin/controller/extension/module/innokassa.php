<?php

use Innokassa\MDK\Client;
use Innokassa\MDK\Entities\Atoms\Vat;
use Innokassa\MDK\Entities\Atoms\ReceiptItemType;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ControllerExtensionModuleInnokassa extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);

        try {
            $this->load->library('innokassa/ClientBuilder');
        } catch (Exception $e) {
        }
    }

    /**
     * Генерация страницы настроек
     *
     * @return void
     */
    public function index()
    {
        $this->load->language('extension/module/innokassa');
        $this->document->setTitle($this->language->get('doc_title'));

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->saveSettings();
        }

        // сборка данных страницы
        $data = array_merge(
            $this->getTemplateData(),
            $this->getFormData(),
            //$this->getSettingsLang(),
            $this->getSettingsData(),
            $this->getStatusData()
        );

        $this->response->setOutput($this->load->view('extension/module/innokassa', $data));
    }

    //**********************************************************************

    /**
     * Получить данные шаблона страницы
     *
     * @return array
     */
    public function getTemplateData()
    {
        $data = [];
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');
        $data['heading_title'] = $this->language->get('doc_title');
        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(
                'common/dashboard',
                'user_token=' . $this->session->data['user_token'],
                true
            )
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link(
                'marketplace/extension',
                'user_token=' . $this->session->data['user_token'] . '&type=module',
                true
            )
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/module/innokassa',
                'user_token=' . $this->session->data['user_token'],
                true
            )
        ];

        return $data;
    }

    /**
     * Получить основные данные формы (кнопки, заголовок, action)
     *
     * @return array
     */
    public function getFormData()
    {
        $data = [];
        $data['button_save'] = $this->language->get('button_save');
        $data['settings_edit'] = $this->language->get('settings_edit');
        $data['action'] = $this->url->link(
            'extension/module/innokassa',
            'user_token=' . $this->session->data['user_token'],
            true
        );
        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token=' . $this->session->data['user_token'] . '&type=module',
            true
        );

        return $data;
    }

    /**
     * Получить данные перевода для настроек
     *
     * @return array
     */
    /*public function getSettingsLang()
    {
        $data = [];
        $data['entry_actor_id'] = $this->language->get('entry_actor_id');
        $data['entry_actor_token'] = $this->language->get('entry_actor_token');
        $data['entry_cashbox'] = $this->language->get('entry_cashbox');
        $data['entry_taxation'] = $this->language->get('entry_taxation');
        $data['entry_location'] = $this->language->get('entry_location');

        $data['help_actor_id'] = $this->language->get('help_actor_id');
        $data['help_actor_token'] = $this->language->get('help_actor_token');
        $data['help_cashbox'] = $this->language->get('help_cashbox');
        $data['help_taxation'] = $this->language->get('help_taxation');
        $data['help_location'] = $this->language->get('help_location');
        $data['help_order_status1'] = $this->language->get('help_order_status1');
        $data['help_order_status2'] = $this->language->get('help_order_status2');
        $data['help_scheme'] = $this->language->get('help_scheme');
        $data['help_shipping_vat'] = $this->language->get('help_shipping_vat');
        $data['help_pipeline'] = $this->language->get('help_pipeline');

        $data['text_scheme2'] = $this->language->get('text_scheme2');
        $data['text_scheme12'] = $this->language->get('text_scheme12');

        return $data;
    }*/

    /**
     * Получить данные настроек
     *
     * @return array
     */
    public function getSettingsData()
    {
        $data = [];
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting("module_innokassa");
        $data = array_merge($data, $settings);

        $data['taxations'] = [];
        $taxations = Innokassa\MDK\Entities\Atoms\Taxation::all();
        foreach ($taxations as $taxation) {
            $data['taxations'][$taxation->getCode()] = $taxation->getName();
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = array_merge(
            [[
                'order_status_id' => 0,
                'name' => '-- Не выбрано'
            ]],
            $this->model_localisation_order_status->getOrderStatuses()
        );

        for ($i = 1; $i <= 2; ++$i) {
            if (array_key_exists("module_innokassa_order_status{$i}", $this->request->post)) {
                $data["module_innokassa_order_status{$i}"] = $this->request->post["module_innokassa_order_status{$i}"];
            } else {
                $data["module_innokassa_order_status{$i}"] = $this->config->get("module_innokassa_order_status{$i}");
            }
        }

        $data['vats'] = [];
        $vats = [
            new Vat(Vat::CODE_20),
            new Vat(Vat::CODE_10),
            new Vat(Vat::CODE_0),
            new Vat(Vat::CODE_WITHOUT)
        ];
        foreach ($vats as $vat) {
            $data['vats'][$vat->getCode()] = $vat->getName();
        }

        $data['item_types'] = [];
        $receiptItemTypes = [];
        $receiptItemTypes[] = new ReceiptItemType(ReceiptItemType::PRODUCT);
        $receiptItemTypes[] = new ReceiptItemType(ReceiptItemType::WORK);
        $receiptItemTypes[] = new ReceiptItemType(ReceiptItemType::SERVICE);
        $receiptItemTypes[] = new ReceiptItemType(ReceiptItemType::PAYMENT);
        foreach ($receiptItemTypes as $receiptItenType) {
            $data['item_types'][$receiptItenType->getCode()] = $receiptItenType->getName();
        }

        $data['module_innokassa_shipping_vat'] = $this->config->get('module_innokassa_shipping_vat');

        $url = 'http' . ((isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) ? 's' : '') . '://'
            . $_SERVER['HTTP_HOST']
            . '/index.php?route=extension/module/innokassa/pipeline&secret='
            . $this->config->get('module_innokassa_pipeline_secret');
        $data['module_innokassa_pipeline_url'] = $url;

        return $data;
    }

    /**
     * Получить данные статусов
     *
     * @return array
     */
    public function getStatusData()
    {
        $data = [];

        // если было успешное изменение настроек - показываем сообщение
        if (isset($this->session->data['settings_success'])) {
            $data['settings_success'] = $this->language->get('settings_success');
            unset($this->session->data["settings_success"]);
        } else {
            $data['settings_success'] = false;
        }

        // если есть ошибки - показываем
        if (isset($this->session->data['settings_error'])) {
            $data['error_warning'] = implode("<br/>", $this->session->data["settings_error"]);
            unset($this->session->data["settings_error"]);
        } else {
            $data['error_warning'] = false;
        }

        return $data;
    }

    //**********************************************************************

    /**
     * Валидация введенных настроек.
     * Описания ошибок доступны в $this->errors
     *
     * @return bool
     */
    public function validateSettings()
    {
        $settings = $this->request->post;

        try {
            $transfer = new Innokassa\MDK\Net\Transfer(
                new Innokassa\MDK\Net\NetClientCurl(),
                new Innokassa\MDK\Net\ConverterApi()
            );
            $conn = new Innokassa\MDK\Services\ConnectorBase($transfer);
            $conn->testSettings(new SettingsConcrete($settings));
        } catch (Innokassa\MDK\Exceptions\SettingsException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Сохранение настроек (с валидацией).
     * Описания ошибок доступны в $this->session->data['settings_error'].
     * Описание успеха в $this->session->data['settings_success']
     *
     * @return bool
     */
    public function saveSettings()
    {
        if ($this->validateSettings()) {
            $this->load->model('setting/setting');
            $settings = $this->request->post;
            $settings['module_innokassa_pipeline_secret'] = $this->config->get('module_innokassa_pipeline_secret');
            $this->model_setting_setting->editSetting('module_innokassa', $settings);
            $this->session->data['settings_success'] = $this->language->get('settings_success');
            $this->response->redirect(
                $this->url->link(
                    'extension/module/innokassa',
                    'user_token=' . $this->session->data['user_token'],
                    true
                )
            );
            return true;
        } else {
            $this->session->data['settings_error'] = $this->errors;
            return false;
        }
    }

    //######################################################################

    /**
     * Установка модуля
     *
     * @return void
     */
    public function install()
    {
        $this->load->model('extension/module/innokassa');
        $this->model_extension_module_innokassa->install();

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting(
            'module_innokassa',
            [
                'module_innokassa_pipeline_secret' => md5(time()),
                'module_innokassa_item_type' => ReceiptItemType::PRODUCT
            ]
        );

        $this->load->model('setting/event');

        // события при которых будет показ кнопки перехода в CRM
        $this->model_setting_event->addEvent(
            'innokassa',
            'admin/view/sale/order_form/after',
            'extension/module/innokassa/eventSaleOrderFormInfoAfter'
        );
        $this->model_setting_event->addEvent(
            'innokassa',
            'admin/view/sale/order_info/after',
            'extension/module/innokassa/eventSaleOrderFormInfoAfter'
        );

        // события при которых будет автоматическая фискализация
        $this->model_setting_event->addEvent(
            'innokassa',
            'catalog/model/checkout/order/addOrderHistory/after',
            'extension/module/innokassa/changeOrderStatus'
        );
    }

    /**
     * Удаление модуля
     *
     * @return void
     */
    public function uninstall()
    {
        $this->load->model('extension/module/innokassa');
        $this->model_extension_module_innokassa->uninstall();

        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('innokassa');
    }

    //######################################################################

    /**
     * Обработчик событий:
     *  - admin/view/sale/order_form/after
     *  - admin/view/sale/order_info/after
     *
     * @param string $route
     * @param array $args
     * @param string $output
     * @return void
     */
    public function eventSaleOrderFormInfoAfter(&$route, &$args, &$output)
    {
        if (!$this->isEnableModule()) {
            return;
        }

        $script = '<script src="/admin/view/template/extension/module/innokassa-btn.js"></script>';
        $needle = '</div>';
        $pos = strripos($output, $needle) + strlen($needle);
        $output = substr($output, 0, $pos) . $script . substr($output, $pos);
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /**
     * Массив ошибок при сохранении настроек
     *
     * @var array
     */
    private $errors = [];

    //######################################################################

    /**
     * Включен ли модуль
     *
     * @return boolean
     */
    private function isEnableModule()
    {
        if (!$this->ClientBuilder) {
            return false;
        }

        $client = $this->ClientBuilder->getClient();
        $settings = $client->componentSettings();

        return ($settings->get('module_innokassa_status') == 1);
    }
}
