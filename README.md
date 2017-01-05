# MultiStepForm #

## Introduction ##

MultiStepForm is Plugin to help your multi step form.

## Requirements ##

- CakePHP >= 3.0

## Setup ##

AppFormHelper
```
<?php
use MultiStepForm\View\Helper\Traits\MultiStepFormHelperTrait;

class AppFormHelper extends FormHelper
{
    use MultiStepFormHelperTrait;
    
    // Submit用のボタン名称設定
    public $nextlabel = '確認';    
    public $backlabel = '戻る';
    
}
```

AppView.php
```
<?php
namespace App\View;

use Cake\View\View;

class AppView extends View
{
    /**
    * initialize
    */
    public function initialize()
    {
        // AppFormHelperの読み込み設定
        $this->loadHelper('Form', [
            'className' => 'AppForm',
        ]);
    }
}

```

## Examples ##

1. Simple Form

```
<?php
namespace App\Controller;

use MultiStepForm\Controller\Traits\MultiStepFormTrait;

/**
* TopicsController
*/
class TopicsController extends Controller
{
    use MultiStepFormTrait;
    
    /**
    * initialize
    */
    public function initialize()
    {
        parent::initialize();
        // 通常のアクションとしてアクセスが必要な場合は whitelist に設定する
        $this->loadComponent('MultiStepForm.MultiStepForm', [
            'whitelist' => [
                'index',
                'view',
                'add',
                'edit',
                'delete'
            ]
        ]);
    }

    /**
    * add
    */
    public function add()
    {
        $this->MultiStepForm->dispatch();
    }

    /**
    * add_input
    */
    public function add_input()
    {
        $topic = $this->MultiStepForm->getEntity();
        $this->set(compact('topic'));
        $this->render('add_input');
    }

    /**
    * add_confirm
    */
    public function add_confirm()
    {
        $topic = $this->MultiStepForm->getEntity();
        $this->set(compact('topic'));
        $this->render('add_confirm');
    }

    /**
    * add_finish
    */
    public function add_finish()
    {
        $data = $this->MultiStepForm->getData();
        $topic = $this->Topics->newEntity($data);
        if($this->Topics->save($topic)){
            $this->Flash->success('Success!');
        } else {
            $this->Flash->error('Error!');
        }

        $this->redirect(['action' => 'index']);
    }
}
```

```
// Submit Button
// 次の画面に進むためのボタン表示
// 第一引数は設置する画面名
// 第二引数はその他の設定
// nextlabel：ボタン名
// nextClass：ボタンのクラス
<?= $this->Form->next(); ?>
<?= $this->Form->next('add_input'); ?>
<?= $this->Form->next('add_input', [
    'nextlabel' => '次へ'
]); ?>
OR
// 次の画面もしくは、前の画面に進むためのボタン表示
// 第一引数は設置する画面名
// 第二引数はその他の設定
// nextlabel：ボタン名
// backlabel：ボタン名
// nextClass：ボタンのクラス
// backClass：ボタンのクラス
<?= $this->Form->nextOrBack(); ?>
<?= $this->Form->nextOrBack('add_input'); ?>
<?= $this->Form->nextOrBack('add_input', [
    'nextlabel' => '次へ',
    'backlabel' => '戻る',
]); ?>
```

2. Custom Form

Controller
```
<?php
namespace App\Controller;

use MultiStepForm\Controller\Traits\MultiStepFormTrait;

/**
* TopicsController
*/
class TopicsController extends Controller
{
    use MultiStepFormTrait;
    
    /**
    * initialize
    */
    public function initialize()
    {
        parent::initialize();
        // 通常のアクションとしてアクセスが必要な場合は whitelist に設定する
        $this->loadComponent('MultiStepForm.MultiStepForm', [
            'whitelist' => [
                'index',
                'view',
                'add',
                'edit',
                'delete'
            ]
        ]);
    }

    /**
    * edit
    */
    public function edit($id = null)
    {
        
        // $modelClass以外のTableを使用する場合
        $this->MultiStepForm->setTable('Projects');

        // デフォルトの設定を全て上書きする場合
        $this->MultiStepForm->setConfig([
            'edit_first_input' => [
                'id' => $id,
                'back' => false,
                'next' => 'edit_second_input',
                'validate' => 'default',
                'multiple' => false,
                'associated' => []
            ],
            'edit_second_input' => [
                'id' => $id,
                'back' => 'edit_first_input',
                'next' => 'edit_third_input',
                'validate' => default',
                'multiple' => false,
                'associated' => []
            ],
            'edit_third_input' => [
                'id' => $id,
                'back' => 'edit_second_input',
                'next' => false,
                'validate' => 'default',
                'multiple' => false,
                'associated' => []
            ],            
        ]);
        
        // デフォルトの設定を一部上書きする場合
        $this->MultiStepForm->mergeConfig([
            'edit_input' => [
                'id' => $id
            ]
        ]);
        
    
        $this->MultiStepForm->dispatch();
    }
```

3. Model Less Form

```
<?php
namespace App\Controller;

use MultiStepForm\Controller\Traits\ModelLessMultiStepFormTrait;

/**
* TopicsController
*/
class TopicsController extends Controller
{
    use ModelLessMultiStepFormTrait;
    
    /**
    * initialize
    */
    public function initialize()
    {
        parent::initialize();
        // 通常のアクションとしてアクセスが必要な場合は whitelist に設定する
        $this->loadComponent('MultiStepForm.ModelLessMultiStepForm', [
            'whitelist' => [
                'index',
                'view',
                'add',
                'edit',
                'delete'
            ]
        ]);
    }
    
    /**
    * add
    */
    public function add()
    {
        $this->MultiStepForm->dispatch();
        
        $form = new Form();
        $this->ModelLessMultiStepForm->setForm($form);
        $this->ModelLessMultiStepForm->dispatch();
    }
    
    /**
    * add_input
    */
    public function add_input()
    {
        $topic = $this->ModelLessMultiStepForm->getData();
        $this->set(compact('topic'));
        $this->render('add_input');
    }

    /**
    * add_confirm
    */
    public function add_confirm()
    {
        $topic = $this->ModelLessMultiStepForm->getData();
        $this->set(compact('topic'));
        $this->render('add_confirm');
    }

    /**
    * add_finish
    */
    public function add_finish()
    {
        $data = $this->ModelLessMultiStepForm->getData();
        $form = $this->ModelLessMultiStepForm->getForm();
        if($form->execute($data)){
            $this->Flash->success('Success!');
        } else {
            $this->Flash->error('Error!');
        }

        $this->redirect(['action' => 'index']);
    }

}
```

