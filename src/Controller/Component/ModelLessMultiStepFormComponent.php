<?php
namespace MultiStepForm\Controller\Component;

use Cake\Controller\Component;
use Cake\Form\Form;
use Cake\Validation\Validation;
use MultiStepForm\ORM\NotUseSetterMarshaller;
use MultiStepForm\Controller\Component\MultiStepFormCoreComponent;

/**
 * ModelLessMultiStepFormComponent
 */
class ModelLessMultiStepFormComponent extends MultiStepFormCoreComponent
{
    /**
     * initialize
     * @author ito
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    /**
    * setForm
    * 使用するテーブルの切り替え処理
    * @author ito
    */
    public function setForm(Form $form)
    {
        $this->Form = $form;
    }

    /**
    * getForm
    * @author ito
    */
    public function getForm()
    {
        return $this->Form;
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
                return $this->goBack();
                break;
            case 'next':
                return $this->goNext();
                break;
            case 'error':
                // バリデーションエラーの結果を変換
                return $this->throughHere();
                break;
            case 'through':
                return $this->throughHere();
                break;
            case 'first':
                $sessionKey = $this->publishKey(); // セッションキーのセット
                $this->request->data[$this->hiddenKey] = $sessionKey;
                $this->writeData($sessionKey, $this->request->data);
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
        $actionConfig = $this->getActionConfig();
        $validate = $actionConfig['validate'];

        // バリデータFalseの場合はバリデーション行わずにTrueを返す
        if (!$validate) {
            return true;
        }

        $requestData = $this->request->data;
        $this->Form->validate($requestData);
        return empty($this->Form->errors());
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
}