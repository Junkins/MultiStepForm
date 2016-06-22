<?php
namespace MultiStepForm\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use Cake\Validation\Validation;
use MultiStepForm\ORM\NotUseSetterMarshaller;

class MultiStepFormComponent extends Component
{
    protected $nextKey   = 'next';
    protected $backKey   = 'back';
    protected $hereKey   = 'here';
    public    $hiddenKey = 'hidden_key';

    protected $whitelist = [];

    /**
    * defaultConfig
    * 初期設定
    *
    *
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
    *
    *
    */
    protected $actionConfig = [];

    /**
     * Initialize properties.
     *
     * @param array $actionConfig The actionConfig data.
     * @return void
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
        $this->Table        = TableRegistry::get($controller->modelClass);

        if (!empty($config['whitelist'])) {
            $this->whitelist = $config['whitelist'];
        }

        // whitelistに含まれていない処理は自動的にautoRender false 設定
        if ( array_search($this->action, $this->whitelist) === false ) {
            $this->controller->autoRender = false;
        }
    }

    /**
    * setTable
    * 使用するテーブルの切り替え処理
    * @author ito
    */
    public function setTable($table){
        $this->Table = TableRegistry::get($table);
    }

    /**
    * setConfig
    *
    * @author ito
    */
    public function setConfig($actionConfig)
    {
        $this->actionConfig = $actionConfig;
    }

    /**
     * [setDefaultConfig description]
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
     * [insertConfig description]
     *
     * @author ito
     */
    public function insertConfig($path, $value)
    {
        $this->actionConfig = Hash::insert($this->actionConfig, $path, $value);
    }

    /**
     * [insertConfig description]
     *
     * @author ito
     */
    public function mergeConfig($merge)
    {
        $this->actionConfig = Hash::merge($this->actionConfig, $merge);
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
    * dispach
    * 処理の振り分け
    * @author ito
    */
    public function dispach()
    {
        $destination = '';
        if (
            $this->request->is('post') ||
            $this->request->is('put')
        ) {
            // POSTリクエストの際の動き
            $destination = $this->whenPost();
        } else {
            // 初回アクセス
            // GETリクエストの際の動き
            // $destination = 'through';
            $destination = $this->whenGet();
        }

        switch ($destination) {
            case 'back':
                $this->entity = $this->dehydration();
                return $this->goBack();
                break;
            case 'next':
                $this->entity = $this->dehydration();
                return $this->goNext();
                break;
            case 'error':
                // バリデーションエラーの結果を変換
                $this->entity = $this->errorEntity;
                return $this->throughHere();
                break;
            case 'through':
                // @todo セッション切れチェック
                // setter off のEntityを作成
                // @todo markNew 設定も必要
                $this->entity = $this->dehydration();
                return $this->throughHere();
                break;
            case 'first':
                $actionConfig = $this->actionConfig;
                $firstConfig = array_shift($actionConfig);
                if (
                    (!empty($firstConfig['id'])) &&
                    (Validation::naturalNumber($firstConfig['id']))
                ) {
                    $id = $firstConfig['id'];
                    $this->entity = $this->Table->get($id, [
                        'contain' => $firstConfig['associated']
                    ]);

                    $sessionKey = $this->publishKey(); // セッションキーのセット
                    $this->entity->{$this->hiddenKey} = $sessionKey;
                    // @todo primary keyに変更
                    $this->entity->id = $firstConfig['id'];

                    // 初期データをセッションにセット
                    $data = $this->entity->toArray();
                    $this->writeData($sessionKey, $data);
                } else {
                    // @todo association対応
                    $newMethod = (isset($actionConfig['multiple']) && $actionConfig['multiple']) ? 'newEntities': 'newEntity';
                    $this->entity = $this->Table->{$newMethod}();
                    $this->entity->{$this->hiddenKey} = $this->publishKey(); // セッションキーのセット
                }

                return $this->displayFirst();
                break;
            default:
                # code...
                break;
        }
    }

    /**
    * getEntity
    *
    * @author ito
    */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
    * getAllData
    *
    * @author ito
    */
    public function getAllData()
    {
        $sessionKey = $this->request->data[$this->hiddenKey];
        return $this->readData($sessionKey);
    }

    /**
    * getConfig
    *
    * @author ito
    */
    public function getConfig($here = '')
    {
        if ( empty($here) ) {
            return $this->actionConfig;
        }

        return $this->actionConfig[$here];
    }

    /**
    * whenGet
    *
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
    *
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
            if (!$this->validation()) {
                return 'error';
            }

            // POSTデータをセッションに書き込み
            $this->mergeData();
            return 'next';
        }

        // POST情報の中に移動先情報が含まれていない場合
        return 'through';
    }

    /**
    * displayFirst
    *
    * @author ito
    */
    protected function displayFirst()
    {
        $actionConfig = $this->actionConfig;
        $first = key(array_slice($actionConfig, 0, 1));
        $firstConfig = array_shift($actionConfig);
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
        return $this->controller->{$here}();
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
        return $this->controller->{$next}();
    }

    /**
    * goBack
    *
    * @author ito
    */
    protected function goBack()
    {
        $actionConfig = $this->getActionConfig();
        $back = $actionConfig['back'];
        return $this->controller->{$back}();
    }

    /**
    * isFirstAccess
    *
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
    *
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
    *
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
    * validation
    *
    * @author ito
    */
    protected function validation()
    {
        $requestData = $this->request->data;
        $actionConfig = $this->getActionConfig();
        $validate = $actionConfig['validate'];

        // バリデータFalseの場合はバリデーション行わずにTrueを返す
        if (!$validate) {
            return true;
        }

        // バリデーション時の(create, update)の制御用にEntityの作成方法を切り替える。
        if (!empty($actionConfig['id'])) {
            $entity = $this->Table->get($actionConfig['id']);
            $patchMethod = (isset($actionConfig['multiple']) && $actionConfig['multiple']) ? 'patchEntities': 'patchEntity';
            $entity = $this->Table->{$patchMethod}($entity, $requestData, ['validate' => $validate]);
        } else {
            $entity = $this->handleNewEntity($requestData);
        }

        if ($this->isError($entity)) {
            $this->errorEntity = $entity;
            return false;
        }

        return true;
    }

    /**
    * filterData
    *
    * @author ito
    */
    protected function filterData($data)
    {
        $filtered = [
            $this->nextKey,
            $this->backKey,
            $this->hereKey,
            $this->hiddenKey,
        ];

        foreach ($filtered as $field) {
            if (array_key_exists($field, $data)) {
                unset($data[$field]);
            }
        }

        return $data;
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
    * publishKey
    * セッションデータのキー発行
    * @author ito
    */
    public function publishKey()
    {
        return Security::hash(time() . rand());
    }

    /**
    * dehydration
    *
    * @author ito
    */
    protected function dehydration()
    {
        $actionConfig = $this->getActionConfig();

        $sessionKey = $this->request->data[$this->hiddenKey];
        $sessionData = $this->readData($sessionKey);
        // セッションデータからEntityを作成 new Entity
        $entityClass = $this->Table->entityClass();
        $marshaller = new NotUseSetterMarshaller($this->Table);

        $entity = null;
        if (
            isset($actionConfig['multiple']) &&
            $actionConfig['multiple']
        ) {
            foreach ($sessionData[$this->Table->alias()] as $value) {
                $data = [];
                foreach ($value as $k => $v) {
                    $data[$k] = $v;
                }
                $entity[] = $marshaller->publish($data, $actionConfig);
            }
            $entity[$this->hiddenKey] = $sessionKey;
        } else {
            $entity = $marshaller->publish($sessionData, $actionConfig);
        }

        return $entity;
    }

    /**
     * isWhitelist
     *
     *
     * @author ito
     */
    public function isWhitelist($action)
    {
        return (array_search($action, $this->whitelist) !== false);
    }

    /**
     * handleNewEntity
     *
     *
     * @author ito
     */
    private function handleNewEntity($data)
    {
        $actionConfig   = $this->getActionConfig();
        $validate       = $actionConfig['validate'];

        $sessionKey     = $data[$this->hiddenKey];
        $newMethod      = ($this->isMultiple()) ? 'newEntities' : 'newEntity';
        $data           = ($this->isMultiple()) ? $data[$this->Table->alias()] : $data;
        $entity         =  $this->Table->{$newMethod}($data, ['validate' => $validate]);

        if ( is_array($entity) ) {
            $entity[$this->hiddenKey] = $sessionKey;
        }

        return $entity;
    }

    /**
     * isError
     *
     * @author ito
     * @return boolean
     */
    protected function isError($entity)
    {
        $actionConfig = $this->getActionConfig();

        if (
            isset($actionConfig['multiple']) &&
            $actionConfig['multiple']
        ) {
            foreach ($entity as $key => $value) {
                if (
                    method_exists($value, 'errors') &&
                    !empty($value->errors())
                ) {
                    return true;
                }
            }
        } else {
            return (!empty($entity->errors()));
        }

        return false;
    }

    /**
     * isMultiple
     *
     *
     * @author ito
     */
    protected function isMultiple()
    {
        $actionConfig = $this->getActionConfig();
        return (isset($actionConfig['multiple']) && $actionConfig['multiple']);
    }
}
