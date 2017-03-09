<?php
namespace MultiStepForm\Controller\Component;

use Cake\ORM\TableRegistry;
use Cake\Validation\Validation;
use MultiStepForm\ORM\NotUseSetterMarshaller;
use MultiStepForm\Controller\Component\MultiStepFormCoreComponent;

/**
 * MultiStepFormComponent
 */
class MultiStepFormComponent extends MultiStepFormCoreComponent
{
    /**
     * Initialize properties.
     *
     * @param array $actionConfig The actionConfig data.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $controller         = $this->_registry->getController();
        $this->Table = TableRegistry::get($controller->modelClass);
    }

    /**
    * setTable
    * 使用するテーブルの切り替え処理
    * @author ito
    */
    public function setTable($table)
    {
        $this->Table = TableRegistry::get($table);
    }

    /**
    * getEntity
    * @author ito
    */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
    * dispatch
    * 処理の振り分け
    * @author ito
    */
    public function dispatch()
    {
        parent::dispatch();

        $destination = '';
        if (
            $this->request->is('post') ||
            $this->request->is('put')
        ) {
            // POSTリクエストの際の動き
            $destination = $this->whenPost();
        } else {
            // GETリクエストの際の動き(初回アクセス)
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
                break;
        }
    }

    /**
    * validation
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
     * isError
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
            foreach ($entity as $value) {
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
    * dehydration
    * @author ito
    */
    protected function dehydration()
    {
        $actionConfig = $this->getActionConfig();

        $sessionKey = $this->request->data[$this->hiddenKey];
        $sessionData = $this->readData($sessionKey);
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
    * mergeData
    * リクエストデータとセッションデータのマージ
    * @author ito
    */
    protected function mergeData()
    {
        $requestData = $this->request->data;
        $sessionKey = $requestData[$this->hiddenKey];
        $sessionData = $this->readData($sessionKey);

        $entity = $this->handleNewEntity($requestData);
        if (!$this->isMultiple()) {
            $requestData = $this->fieldAdjustment($requestData);
        } else {
            foreach ($requestData as $key => $data) {
                $requestData[$key] = $this->fieldAdjustment($data);
            }
        }

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
     * fieldAdjustment
     * @author ito
     */
    private function fieldAdjustment($data)
    {
        $sampleEntity = $this->handleNewEntity($data);
        $sampleArray  = $sampleEntity->toArray();
        $setFields    = array_diff_key($sampleArray, $data);
        $unsetFields  = array_diff_key($data, $sampleArray);
        // 別フィールドへのset()を考慮して、set()した際に新規に作られたフィールドをセット
        foreach ($setFields as $key => $value) {
            $data[$key] = $value;
        }
        // set()した際に削除されたフィールドをアンセット
        foreach (array_keys($unsetFields) as $key) {
            unset($data[$key]);
        }
        // バリデーションエラーになったフィールドをセット
        foreach ($sampleEntity->invalid() as $key => $value) {
            // ファイル形式のデータ場合、フィールドにセットしない
            if ($this->isFileData($value)) {
                continue;
            }
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * handleNewEntity
     * @author ito
     */
    protected function handleNewEntity($data)
    {
        $actionConfig   = $this->getActionConfig();
        $validate       = $actionConfig['validate'];

        $sessionKey     = $data[$this->hiddenKey];
        $newMethod      = ($this->isMultiple()) ? 'newEntities' : 'newEntity';
        $data           = ($this->isMultiple()) ? $data[$this->Table->alias()] : $data;
        $entity         = $this->Table->{$newMethod}($data, ['validate' => $validate]);

        if (is_array($entity)) {
            $entity[$this->hiddenKey] = $sessionKey;
        }

        return $entity;
    }

    /**
     * isMultiple
     * @author ito
     */
    protected function isMultiple()
    {
        $actionConfig = $this->getActionConfig();
        return (isset($actionConfig['multiple']) && $actionConfig['multiple']);
    }

    /**
     * isFileData
     * @author ito
     */
    private function isFileData($data)
    {
        if ( !is_array($data) ) {
            return false;
        }

        $result = (
            array_key_exists('name', $data) &&
            array_key_exists('type', $data) &&
            array_key_exists('tmp_name', $data) &&
            array_key_exists('error', $data) &&
            array_key_exists('size', $data)
        );

        return $result;
    }
}
