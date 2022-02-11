<?php

use Innokassa\MDK\Client;
use Innokassa\MDK\Net\Transfer;
use Innokassa\MDK\Net\ConverterApi;
use Innokassa\MDK\Logger\LoggerFile;
use Innokassa\MDK\Net\NetClientCurl;
use Innokassa\MDK\Entities\Atoms\Vat;
use Innokassa\MDK\Services\ManualBase;
use Innokassa\MDK\Entities\ReceiptItem;
use Innokassa\MDK\Services\PrinterBase;
use Innokassa\MDK\Services\PipelineBase;
use Innokassa\MDK\Storage\ReceiptFilter;
use Innokassa\MDK\Services\AutomaticBase;
use Innokassa\MDK\Services\ConnectorBase;
use Innokassa\MDK\Storage\ConverterStorage;
use Innokassa\MDK\Entities\Atoms\ReceiptType;
use Innokassa\MDK\Entities\Primitives\Amount;
use Innokassa\MDK\Entities\Primitives\Notify;
use Innokassa\MDK\Collections\ReceiptItemCollection;

include_once(DIR_SYSTEM . 'library/innokassa/mdk/src/autoload.php');
include_once(DIR_SYSTEM . 'library/innokassa/SettingsConcrete.php');
include_once(DIR_SYSTEM . 'library/innokassa/ReceiptAdapterConcrete.php');
include_once(DIR_SYSTEM . 'library/innokassa/ReceiptStorageConcrete.php');

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ControllerExtensionModuleInnokassa extends Controller
{
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
            $this->getSettingsLang(),
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
    public function getSettingsLang()
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

        $data['text_scheme2'] = $this->language->get('text_scheme2');
        $data['text_scheme12'] = $this->language->get('text_scheme12');

        return $data;
    }

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
        $vats = Innokassa\MDK\Entities\Atoms\Vat::all();
        foreach ($vats as $vat) {
            $data['vats'][$vat->getCode()] = $vat->getName();
        }

        $data['module_innokassa_shipping_vat'] = $this->config->get('module_innokassa_shipping_vat');

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
                new Innokassa\MDK\Net\ConverterApi(),
                $settings['module_innokassa_actor_id'],
                $settings['module_innokassa_actor_token'],
                $settings['module_innokassa_cashbox'],
                new Innokassa\MDK\Logger\LoggerFile()
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
            $this->model_setting_setting->editSetting('module_innokassa', $this->request->post);
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

        $this->load->model('setting/event');
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
     *  - admin/view/sale/order_form/afte
     *  - admin/view/sale/order_info/after
     *
     * @param string $route
     * @param array $args
     * @param string $output
     * @return void
     */
    public function eventSaleOrderFormInfoAfter(&$route, &$args, &$output)
    {
        $script = [
            '<script src="/admin/view/template/extension/module/innokassa-btn-new-receipt.js"></script>',
            '<script src="/admin/view/template/extension/module/innokassa-rb4cms.js"></script>'
        ];
        $needle = '</div>';
        $pos = strripos($output, $needle) + strlen($needle);
        $output = substr($output, 0, $pos) . implode('', $script) . substr($output, $pos);

        $modal = '<div id="receipt-builder-window" class="modal"> \
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title"></h4>
                    </div>
                    <div class="modal-body">
                    </div>
                </div>
            </div>
        </div>';
        $needle = '</body>';
        $pos = strripos($output, $needle);
        $output = substr($output, 0, $pos) . $modal . substr($output, $pos);
    }

    //######################################################################

    /**
     * Ajax запрос на получение данных о заказе
     * @todo сделать проверку валидности настроек
     *
     * @return void
     */
    public function ajaxGetOrder()
    {
        $this->response->addHeader('Content-Type: application/json');

        $idOrder = $this->request->get["order_id"];

        $this->load->model('sale/order');

        // если заказа с таким id не существует
        if (!$this->model_sale_order->getOrder($idOrder)) {
            return $this->responseError("Заказ #$idOrder не найден");
        }

        $conv = new \Innokassa\MDK\Storage\ConverterStorage();

        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting("module_innokassa");
        $adapter = new ReceiptAdapterConcrete($this->model_sale_order, new SettingsConcrete($settings));

        try {
            $items = $adapter->getItems($idOrder, 1);
            $notify = $adapter->getNotify($idOrder, 1);
        } catch (Exception $e) {
            return $this->responseError($e->getMessage());
        }

        try {
            $client = $this->getClient();
        } catch (Exception $e) {
            return $this->responseError($e->getMessage());
        }

        $printer = $client->servicePrinter();
        $receiptStorage = $client->componentStorage();
        $receipts = $receiptStorage->getCollection(
            (new ReceiptFilter())
                ->setOrderId($idOrder)
        );
        $printables = [];
        foreach ($receipts as $receipt) {
            $printables[] = [
                'uuid' => $receipt->getUUID()->get(),
                'link' => $printer->getLinkRaw($receipt),
                'type' => $receipt->getType(),
                'subType' => $receipt->getSubType(),
                'amount' => $receipt->getAmount()->get(Amount::CASHLESS),
            ];
        }

        $this->response->setOutput(json_encode(
            [
                "success" => true,
                "items" => $conv->itemsToArray($items),
                "notify" => $conv->notifyToArray($notify),
                "printables" => $printables
            ]
        ));
    }

    /**
     * Ajax запрос на ручную фискализацию заказа
     * @todo сделать проверку валидности настроек
     *
     * @return void
     */
    public function ajaxHandFiscal()
    {
        $this->response->addHeader('Content-Type: application/json');

        $idOrder = $this->request->get["order_id"];

        $this->load->model('sale/order');

        // если заказа с таким id не существует
        if (!$this->model_sale_order->getOrder($idOrder)) {
            return $this->responseError("Заказ #$idOrder не найден");
        }

        $notifyArr = $this->request->post["notify"];
        $notify = new Notify();
        if (isset($notifyArr['email'])) {
            $notify->setEmail($notifyArr['email']);
        } elseif (isset($notifyArr['phone'])) {
            $notify->setPhone($notifyArr['phone']);
        }

        $itemsArr = $this->request->post["items"];
        $items = new ReceiptItemCollection();
        foreach ($itemsArr as $itemArr) {
            $item = new ReceiptItem();
            $item
                ->setType($itemArr['type'])
                ->setName($itemArr['name'])
                ->setPrice($itemArr['price'])
                ->setQuantity($itemArr['quantity'])
                ->setPaymentMethod($itemArr['payment_method'])
                ->setVat(new Vat($itemArr['vat']));
            $items[] = $item;
        }

        try {
            $client = $this->getClient();
        } catch (Exception $e) {
            return $this->responseError($e->getMessage());
        }

        $manual = $client->serviceManual();

        $type = $this->request->post["type"];
        try {
            if ($type == ReceiptType::COMING) {
                $manual->fiscalize($idOrder, $items, $notify);
            } elseif ($type == ReceiptType::REFUND_COMING) {
                $manual->refund($idOrder, $items, $notify);
            } else {
                throw new Exception("Неверный тип чека - $type");
            }
        } catch (Exception $e) {
            return $this->responseError($e->getMessage());
        }

        $this->response->setOutput(json_encode([
            'success' => true
        ]));
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

    /**
     * Клиент MDK
     *
     * @var Client
     */
    private $client = null;

    //######################################################################

    /**
     * Отпрвка ответа с сообщением об ошибке
     *
     * @param string $error
     * @return void
     */
    private function responseError(string $error)
    {
        $this->response->setOutput(json_encode(
            [
                "success" => false,
                "error" => $error
            ]
        ));
    }

    /**
     * Получить клиент MDK
     *
     * @return Client
     */
    private function getClient()
    {
        if (!$this->client) {
            $this->load->model('sale/order');
            $this->load->model('setting/setting');
            $this->load->model('extension/module/innokassa');
            $this->model_extension_module_innokassa->install();

            $settings = new SettingsConcrete($this->model_setting_setting->getSetting("module_innokassa"));
            $adapter = new ReceiptAdapterConcrete($this->model_sale_order, $settings);
            $storage = new ReceiptStorageConcrete(
                new ConverterStorage(),
                $this->model_extension_module_innokassa->getDB(),
                $this->model_extension_module_innokassa->getTableName()
            );

            $logger = new LoggerFile();

            $transfer = new Transfer(
                new NetClientCurl(),
                new ConverterApi(),
                $settings->getActorId(),
                $settings->getActorToken(),
                $settings->getCashbox(),
                $logger
            );

            $automatic = new AutomaticBase($settings, $storage, $transfer, $adapter);
            $manual = new ManualBase($storage, $transfer, $settings);
            $pipeline = new PipelineBase($storage, $transfer);
            $printer = new PrinterBase($storage, $transfer);
            $connector = new ConnectorBase($transfer);

            $this->client = new Client(
                $settings,
                $adapter,
                $storage,
                $automatic,
                $manual,
                $pipeline,
                $printer,
                $connector,
                $logger
            );
        }

        return $this->client;
    }
}
