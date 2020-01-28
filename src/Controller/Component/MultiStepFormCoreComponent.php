<?php
namespace MultiStepForm\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\Security;

/**
 * MultiStepFormCoreComponent
 */
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
            'back'                  => false,
            'next'                  => 'confirm',
            'validate'              => 'default',
            'associated'            => [],
            'validateAssociated'    => []
        ],
        'confirm' => [
            'back'                  => 'input',
            'next'                  => 'finish',
            'validate'              => false,
            'associated'            => [],
            'validateAssociated'    => []
        ],
        'finish'    => [
            'back'                  => 'confirm',
            'next'                  => false,
            'validate'              => false,
            'associated'            => [],
            'validateAssociated'    => []
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
    private function setDefaultConfig()
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

    ################################################################


    // getConfig/setConfigなどのメソッドを切り替え
    /**
    * setConfigMultistep
    * @author ito
    */
    public function setConfigMultiStep($actionConfig)
    {
        $this->actionConfig = $actionConfig;
    }

    /**
     * insertConfigMultiStep
     * @author ito
     */
    public function insertConfigMultiStep($path, $value)
    {
        $this->actionConfig = Hash::insert($this->actionConfig, $path, $value);
    }

    /**
     * mergeConfigMultiStep
     * @author ito
     */
    public function mergeConfigMultiStep($merge)
    {
        $this->actionConfig = Hash::merge($this->actionConfig, $merge);
    }

    /**
    * getConfigMultiStep
    *
    * @author ito
    */
    public function getConfigMultiStep($here = '')
    {
        if (empty($here)) {
            return $this->actionConfig;
        }

        return $this->actionConfig[$here];
    }

    ################################################################

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
        if ( $this->request->is('post') || $this->request->is('put') ) {

            // Post Max Sizeをオーバーしている場合は処理を行わない
            if ($this->isOverPostMaxSize()) {
                return;
            }

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
    * isOverPostMaxSize
    * @author ito
    */
    protected function isOverPostMaxSize()
    {
        return (
            ($this->request->is('post') || $this->request->is('put'))
            && empty($this->request->data)
        );
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
     * redirectFisrt
     * @author ito
     */
    protected function redirectFisrt()
    {
        $this->controller->Flash->error(__('It is over the post max size'));
        $controller = $this->request->controller;
        $action = $this->request->action;
        
        return $this->controller->redirect(['controller' => $controller, 'action' => $action]);
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
