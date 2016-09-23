# MultiStepForm #

## Introduction ##

MultiStepForm is Plugin to help your multi step form.

## Requirements ##

- CakePHP >= 3.0

## Setup ##
```
use MultiStepForm\View\Helper\Traits\MultiStepFormHelperTrait;

class AppFormHelper extends FormHelper
{
    use MultiStepFormHelperTrait;
}
```

## Examples ##

1. Simple Form

```

use MultiStepForm\Controller\Traits\MultiStepFormTrait;

class TopicsController extends Controller
{
    use MultiStepFormTrait;

    public function initialize()
    {
        parent::initialize();

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

    public function add()
    {
        $this->MultiStepForm->dispach();
    }

    public function add_input()
    {
        $topic = $this->MultiStepForm->getEntity();
        $this->set(compact('topic'));
        $this->render('add_input');
    }

    public function add_confirm()
    {
        $topic = $this->MultiStepForm->getEntity();
        $this->set(compact('topic'));
        $this->render('add_confirm');
    }

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
<?= $this->Form->next(); ?>
OR
<?= $this->Form->nextOrBack(); ?>
```

2. Custom Form
```

```


3. Model Less Form
