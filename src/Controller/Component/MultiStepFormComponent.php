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

            // Post Max Size Overの場合
            if ($this->isOverPostMaxSize()) {
                return $this->redirectFisrt();
            }

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
        if ($this->isMultiple() && isset($requestData[$this->Table->alias()])) {
            $requestData = $this->fieldAdjustmentMultiple($requestData);
        } else {
            $requestData = $this->fieldAdjustment($requestData);
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
        $sourceTable = $this->Table;
        $adjustment = function($data) use (&$adjustment)
        {
            $data    = new \ArrayObject($data);
            if (method_exists($this->Table, 'beforeMarshal')) {
                // バリデーションにかける beforeMarshal を実行
                $options = new \ArrayObject([]);
                $this->Table->dispatchEvent('Model.beforeMarshal', compact('data', 'options'));
            }
            // ArrayObjectから配列に戻す
            $data = $data->getArrayCopy();
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    // hasmanyか確認
                    $associationClass = $this->Table->associations()->get(str_replace('_', '', $key));
                    if (
                        !is_object($associationClass) ||
                        get_class($associationClass) !== 'Cake\ORM\Association\HasMany'
                    ) {
                        continue;
                    }
                    $tmpTable = $this->Table;
                    $this->Table = TableRegistry::get($associationClass->getName());
                    foreach ($value as $innerKey => $innerValue) {
                        if (is_array($innerValue)) {
                            $data[$key][$innerKey] = $adjustment($innerValue);
                        }
                    }
                    $this->Table = $tmpTable;
                }
            }

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
        };

        $dataAdjustmented = $adjustment($data);
        $this->Table = $sourceTable;
        return $dataAdjustmented;
    }

    /**
     * fieldAdjustmentMultiple
     * @author ito
     */
    private function fieldAdjustmentMultiple($data)
    {
        $sourceTable = $this->Table;
        $data    = new \ArrayObject($data);
        if (method_exists($this->Table, 'beforeMarshal')) {
            // バリデーションにかける beforeMarshal を実行
            $options = new \ArrayObject([]);
            $this->Table->dispatchEvent('Model.beforeMarshal', compact('data', 'options'));
        }
        // ArrayObjectから配列に戻す
        $data = $data->getArrayCopy();

        $dataMultiple[$this->Table->alias()] = $data[$this->Table->alias()];
        unset($data[$this->Table->alias()]);
        $dataMeta = $this->fieldAdjustment($data);

        $sampleEntities = $this->handleNewEntity($dataMultiple);

        foreach ($sampleEntities as $entityKey => $sampleEntity) {

            $sampleArray  = $sampleEntity->toArray();
            $sampleData   = $dataMultiple[$this->Table->alias()][$entityKey];
            $setFields    = array_diff_key($sampleArray, $sampleData);
            $unsetFields  = array_diff_key($sampleData, $sampleArray);

            // 別フィールドへのset()を考慮して、set()した際に新規に作られたフィールドをセット
            foreach ($setFields as $key => $value) {
                $dataMultiple[$this->Table->alias()][$entityKey][$key] = $value;
            }

            // set()した際に削除されたフィールドをアンセット
            foreach (array_keys($unsetFields) as $key) {
                unset($dataMultiple[$this->Table->alias()][$entityKey][$key]);
            }

            // バリデーションエラーになったフィールドをセット
            foreach ($sampleEntity->invalid() as $key => $value) {
                // ファイル形式のデータ場合、フィールドにセットしない
                if ($this->isFileData($value)) {
                    continue;
                }
                $dataMultiple[$this->Table->alias()][$entityKey][$key] = $value;
            }
        }

        $dataAdjustmented = $this->fieldAdjustment($dataMeta);
        $dataAdjustmented[$this->Table->alias()] = $dataMultiple[$this->Table->alias()];
        $this->Table = $sourceTable;
        return $dataAdjustmented;
    }

    /**
     * handleNewEntity
     * @author ito
     */
    protected function handleNewEntity($data)
    {
        $actionConfig   = $this->getActionConfig();
        $validate       = $actionConfig['validate'];

        if ($this->isMultiple() && isset($data[$this->Table->alias()])) {
            $newMethod = 'newEntities';
            $data = $data[$this->Table->alias()];
        } else {
            $newMethod = 'newEntity';
            $data = $data;
        }

        $entity = $this->Table->{$newMethod}($data, ['validate' => $validate]);
        if (!empty($data[$this->hiddenKey])) {
            $sessionKey = $data[$this->hiddenKey];
            if (is_array($entity)) {
                $entity[$this->hiddenKey] = $sessionKey;
            }
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
