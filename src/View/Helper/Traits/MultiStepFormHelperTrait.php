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
            if (isset($this->View->viewVars['here'])) {
                $here = $this->View->viewVars['here'];
            }
        }

        $nextOptions = [];
        $nextOptions['nextlabel'] = (empty($options['nextlabel']))? $this->nextlabel: $options['nextlabel'];
        $nextOptions['class']     = (empty($options['nextClass']))? $this->nextClass: $options['nextClass'];
        $nextOptions['name']      = 'next';

        $html = '';
        $html = $this->localInfo($here);
        $html .= $this->submit($nextOptions['nextlabel'], $nextOptions);
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
            if (isset($this->View->viewVars['here'])) {
                $here = $this->View->viewVars['here'];
            }
        }

        $backOptions = $nextOptions = $options;

        $nextOptions['label'] = (empty($options['nextlabel']))? $this->nextlabel: $options['nextlabel'];
        $backOptions['label'] = (empty($options['backlabel']))? $this->backlabel: $options['backlabel'];
        $nextOptions['class'] = (empty($options['nextClass']))? $this->nextClass: $options['nextClass'];
        $backOptions['class'] = (empty($options['backClass']))? $this->backClass: $options['nextClass'];

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
