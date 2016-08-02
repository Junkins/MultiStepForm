<?php
namespace MultiStepForm\View\Helper\Traits;

/**
 * MultiStepFormHelperTrait
 *　マルチステップフォーム用確認ボタン等のクラス
 */
trait MultiStepFormHelperTrait
{

    /**
     * [next]
     * @author ito
     *
     */
    public function next($here = null, $options = [])
    {
        if (empty($here)) {
            if (isset($this->view->viewVars['here'])) {
                $here = $this->view->viewVars['here'];
            }
        }

        $options['nextlabel'] = (empty($options['nextlabel']))? '確認': $options['nextlabel'];
        $options['class']     = 'btn_sizeM btn_Black';
        $options['name']      = 'next';

        $html = '';
        $html = $this->localInfo($here);
        $html .= $this->submit($options['nextlabel'], $options);
        return $html;
    }

    /**
     * [nextOrBack]
     * @author ito
     *
     */
    public function nextOrBack($here = null, $options = [])
    {
        if (empty($here)) {
            if (isset($this->view->viewVars['here'])) {
                $here = $this->view->viewVars['here'];
            }
        }

        $backOptions = $nextOptions = $options;

        $nextOptions['label'] = (empty($options['nextlabel']))? '登録': $options['nextlabel'];
        $backOptions['label'] = (empty($options['backlabel']))? '戻る': $options['backlabel'];

        $backOptions['name']  = 'back';
        $nextOptions['name']  = 'next';

        $backOptions['class']  = 'btn_sizeM btn_Black';
        $nextOptions['class']  = 'btn_sizeM btn_Blue';

        $html = '';
        $html = $this->localInfo($here);
        $html .= $this->submitNoDiv($backOptions['label'], $backOptions);
        $html .= $this->submitNoDiv($nextOptions['label'], $nextOptions);
        return $html;
    }

    /**
     * [localInfo]
     * @author ito
     *
     */
    private function localInfo($here)
    {
        $html = '';
        $html .= $this->input('hidden_key', ['type' => 'hidden']);
        $html .= $this->input('here', ['type' => 'hidden', 'value' => $here]);
        return $html;
    }
}
