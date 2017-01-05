<?php
namespace MultiStepForm\View\Helper\Traits;

/**
 * MultiStepFormHelperTrait
 *　マルチステップフォーム用確認ボタン等のクラス
 */
trait MultiStepFormHelperTrait
{

    /**
     * next
     * @access public
     * @author ito
     */
    public function next($here = null, $options = [])
    {
        if (empty($here)) {
            if (isset($this->view->viewVars['here'])) {
                $here = $this->view->viewVars['here'];
            }
        }

        $nextOptions = [];
        $nextOptions['nextLabel'] = (empty($options['nextLabel']) && isset($this->nextLabel))? $this->nextLabel: $options['nextLabel'];
        $nextOptions['class']     = (empty($options['nextClass']) && isset($this->nextClass))? $this->nextClass: $options['nextClass'];
        $nextOptions['name']      = 'next';

        $html = '';
        $html = $this->localInfo($here);
        $html .= $this->submit($nextOptions['nextLabel'], $nextOptions);
        return $html;
    }

    /**
     * back
     * @access public
     * @author ito
     */
    public function back($here = null, $options = [])
    {
        if (empty($here)) {
            if (isset($this->View->viewVars['here'])) {
                $here = $this->View->viewVars['here'];
            }
        }

        $backOptions = [];
        $backOptions['backLabel'] = (empty($options['backLabel']) && isset($this->backLabel))? $this->backLabel: $options['backLabel'];
        $backOptions['class']     = (empty($options['backClass']) && isset($this->backClass))? $this->backClass: $options['backClass'];
        $backOptions['name']      = 'back';

        $html = '';
        $html = $this->localInfo($here);
        $html .= $this->submit($backOptions['backLabel'], $backOptions);
        return $html;
    }

    /**
     * nextOrBack
     * @access public
     * @author ito
     */
    public function nextOrBack($here = null, $options = [])
    {
        if (empty($here)) {
            if (isset($this->view->viewVars['here'])) {
                $here = $this->view->viewVars['here'];
            }
        }

        $backOptions = $nextOptions = $options;

        $nextOptions['label'] = (empty($options['nextLabel']) && isset($this->nextLabel))? $this->nextLabel: $options['nextLabel'];
        $backOptions['label'] = (empty($options['backLabel']) && isset($this->backLabel))? $this->backLabel: $options['backLabel'];
        $nextOptions['class'] = (empty($options['nextClass']) && isset($this->nextClass))? $this->nextClass: $options['nextClass'];
        $backOptions['class'] = (empty($options['backClass']) && isset($this->backClass))? $this->backClass: $options['backClass'];

        $nextOptions['name'] = 'next';
        $backOptions['name'] = 'back';

        $html = '';
        $html = $this->localInfo($here);
        $html .= $this->submitNoDiv($backOptions['label'], $backOptions);
        $html .= $this->submitNoDiv($nextOptions['label'], $nextOptions);
        return $html;
    }

    /**
     * submitNoDiv
     * submitフォームのdivタグを排除
     * @access public
     * @author ito
     */
    public function submitNoDiv($caption = null, array $options = [])
    {
        //現在のテンプレートをロード
        $currentTemplate = $this->templates('submitContainer');
        //submitフォームのdivタグを排除
        $this->templates([
            'submitContainer' => '{{content}}'
        ]);
        //submitフォーム生成
        $submitContent = parent::submit($caption, $options);
        //テンプレートを差し戻す
        $this->templates([
            'submitContainer' => $currentTemplate
        ]);
        return $submitContent;
    }

    /**
     * localInfo
     * @access private
     * @author ito
     */
    private function localInfo($here)
    {
        $html = '';
        $html .= $this->input('hidden_key', ['type' => 'hidden']);
        $html .= $this->input('here', ['type' => 'hidden', 'value' => $here]);
        return $html;
    }

}
