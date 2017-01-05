<?php
namespace MultiStepForm\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\Security;

class MultiStepFormCoreComponent extends Component
{
    public $hiddenKey = 'hidden_key';

    protected $nextKey   = 'next';
    protected $backKey   = 'back';
    protected $hereKey   = 'here';

    protected $whitelist = [];

    /**
    * defaultActionConfig
    * 基本的な流れ「入力 -> 確認 -> 処理」
    * 初期設定
    */
    protected $defaultActionConfig = [
        'input'   => [
            'back'       => false,
            'next'       => 'confirm',
            'validate'   => 'default',
            'associated' => []
        ],
        'confirm' => [
            'back'       => 'input',
            'next'       => 'finish',
            'validate'   => false,
            'associated' => []
        ],
        'finish'    => [
            'back'       => 'confirm',
            'next'       => false,
            'validate'   => false,
            'associated' => []
        ],
    ];

    /**
    * actionConfig
    * アクションに関する設定
    */
    protected $actionConfig = [];

    /**
     * initialize
     * 初期設定
     * @author ito
     */
    public function initialize(array $config)
    {
        $controller         = $this->_registry->getController();
        $this->controller   = $controller;
        $this->response     =& $controller->response;
        $this->request      =& $controller->request;
        $this->action       = $controller->request->action;
        $this->session      = $controller->request->session();

        $this->actionConfig = $this->setDefaultConfig();

        if (!empty($config['whitelist'])) {
            $this->whitelist = $config['whitelist'];
        }

        // whitelistに含まれていない処理は自動的にautoRender false 設定
        if (array_search($this->action, $this->whitelist) === false) {
            $this->controller->autoRender = false;
        }
    }

    ################################################################

    /**
     * setDefaultConfig
     * @author ito
     */
    public function setDefaultConfig()
    {
        $actionConfig = null;

        $defaultActionConfig = $this->defaultActionConfig;
        foreach ($defaultActionConfig as $actionSuffix => $configs) {
            $actionKey = $this->action . '_' . $actionSuffix;

            $actionConfig[$actionKey] = [];
            foreach ($configs as $configName => $configValue) {
                if (
                    (
                        $configName === 'back' ||
                        $configName === 'next'
                    ) &&
                    (
                        $configValue !== false
                    )
                ) {
                    $configValue = $this->action . '_' . $configValue;
                }

                $actionConfig[$actionKey][$configName] = $configValue;
            }
        }

        return $actionConfig;
    }

    /**
    * setConfig
    * @author ito
    */
    public function setConfig($actionConfig)
    {
        $this->actionConfig = $actionConfig;
    }

    /**
     * insertConfig
     * @author ito
     */
    public function insertConfig($path, $value)
    {
        $this->actionConfig = Hash::insert($this->actionConfig, $path, $value);
    }

    /**
     * insertConfig
     * @author ito
     */
    public function mergeConfig($merge)
    {
        $this->actionConfig = Hash::merge($this->actionConfig, $merge);
    }

    /**
    * getConfig
    *
    * @author ito
    */
    public function getConfig($here = '')
    {
        if (empty($here)) {
            return $this->actionConfig;
        }

        return $this->actionConfig[$here];
    }

    /**
    * getActionConfig
    *
    * @author ito
    */
    protected function getActionConfig()
    {
        $key = $this->hereKey;
        $here = $this->request->data[$key];
        $actionConfig = $this->actionConfig[$here];
        $actionConfig[$key] = $here;
        return $actionConfig;
    }

    /**
    * getData
    *
    * @author ito
    */
    public function getData()
    {
        if ( empty($this->request->data[$this->hiddenKey]) ) {
            return [];
        }

        $sessionKey = $this->request->data[$this->hiddenKey];
        return $this->readData($sessionKey);
    }

    ################################################################

    /**
     * dispatch
     * @author ito
     * @return [type] [description]
     */
    public function dispatch()
    {
        if ( $this->request->is('post') ) {

            if ( method_exists($this->controller, 'beforeDispatch') ) {
                $this->controller->beforeDispatch();
            }

            $actionConfig = $this->getActionConfig();
            $callback = '';
            if ( !empty($actionConfig['callback']) ) {
                $callback = $actionConfig['callback'];
            } else {
                $callback = 'before' . Inflector::camelize($actionConfig['here']);
            }

            if ( method_exists($this->controller, $callback) ) {
                $this->controller->{$callback}();
            }
        }
    }

    /**
    * whenGet
    * @author ito
    */
    protected function whenGet()
    {
        // 初回アクセス
        if ($this->isFirstAccess()) {
            return 'first';
        }
        return 'through';
    }

    /**
    * whenPost
    * @author ito
    */
    protected function whenPost()
    {
        // POST情報の中にBACK移動先情報が含まれている場合
        if ($this->isBackRequest()) {
            return 'back';
        }

        // POST情報の中にNEXT移動先情報が含まれている場合
        if ($this->isNextRequest()) {
            // バリデーションチェック
            $result = $this->validation();
            // POSTデータをセッションに書き込み
            $this->mergeData();

            if (!$result) {
                return 'error';
            } else {
                return 'next';
            }
        }

        // POST情報の中に移動先情報が含まれていない場合
        return 'through';
    }

    /**
    * isFirstAccess
    * @author ito
    */
    protected function isFirstAccess()
    {
        $hiddenKey = $this->hiddenKey;
        if (
            !isset($this->request->data[$hiddenKey]) ||
            empty($this->readData($this->request->data[$hiddenKey]))
        ) {
            return true;
        }

        return false;
    }

    /**
    * isNextRequest
    * @author ito
    */
    protected function isNextRequest()
    {
        $requestData = $this->request->data;
        $key = $this->nextKey;
        if (array_key_exists($key, $requestData)) {
            return true;
        }

        return false;
    }

    /**
    * isBackRequest
    * @author ito
    */
    protected function isBackRequest()
    {
        $requestData = $this->request->data;
        $key = $this->backKey;
        if (array_key_exists($key, $requestData)) {
            return true;
        }

        return false;
    }

    /**
     * isWhitelist
     * @author ito
     */
    public function isWhitelist($action)
    {
        return (array_search($action, $this->whitelist) !== false);
    }

    ################################################################

    /**
    * displayFirst
    *
    * @author ito
    */
    protected function displayFirst()
    {
        $actionConfig = $this->actionConfig;
        $first = key(array_slice($actionConfig, 0, 1));
        $this->controller->set('here', $first);
        $this->request->data = $this->getData();
        return $this->controller->{$first}();
    }

    /**
    * throughHere
    *
    * @author ito
    */
    protected function throughHere()
    {
        $actionConfig = $this->getActionConfig();
        $here = $actionConfig['here'];
        $this->controller->set('here', $here);
        $this->request->data = $this->getData();
        return $this->controller->{$here}();
    }

    /**
    * goBack
    * @author ito
    */
    protected function goBack()
    {
        $actionConfig = $this->getActionConfig();
        $back = $actionConfig['back'];
        $this->controller->set('here', $back);
        $this->request->data = $this->getData();
        return $this->controller->{$back}();
    }

    /**
    * goNext
    *
    * @author ito
    */
    protected function goNext()
    {
        $actionConfig = $this->getActionConfig();
        $next = $actionConfig['next'];
        $this->controller->set('here', $next);
        $this->request->data = $this->getData();
        return $this->controller->{$next}();
    }

    ################################################################

    /**
    * readData
    * セッションデータの読み込み
    * @author ito
    */
    protected function readData($sessionKey)
    {
        if (!$this->session->check($sessionKey)) {
            return [];
        }
        return $this->session->read($sessionKey);
    }

    /**
    * writeData
    * セッションデータの書き込み
    * @author ito
    */
    protected function writeData($sessionKey, $data)
    {
        $this->session->write($sessionKey, $data);
    }

    /**
    * mergeData
    * リクエストデータとセッションデータのマージ
    * @author ito
    */
    protected function mergeData()
    {
        $requestData = $this->request->data;
        $sessionKey = $requestData[$this->hiddenKey];
        $sessionData = $this->readData($sessionKey);

        // リクエストデータから余計なデータを削除する。
        // sessionKeyを取得したのちにフィルターをかける
        $requestData = $this->filterData($requestData);

        $writeData = [];
        if (!empty($sessionData)) {
            $writeData = $sessionData;
            foreach ($requestData as $field => $value) {
                $writeData[$field] = $value;
            }
        } else {
            $writeData = $requestData;
        }

        $this->writeData($sessionKey, $writeData);
    }

    /**
    * filterData
    * @author ito
    */
    protected function filterData($data)
    {
        $filtered = [
            $this->nextKey,
            $this->backKey,
            $this->hereKey,
        ];

        foreach ($filtered as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
    * publishKey
    * セッションデータのキー発行
    * @author ito
    */
    public function publishKey()
    {
        return Security::hash(time() . rand());
    }

    ################################################################

}