<?php

namespace Kanboard\Plugin\SensitiveItem\Helper;

use Kanboard\Core\Base;

class SensitiveItemHelper extends Base
{
    public function renderSensitiveCheckbox()
    {
        $value = FALSE;
        $item = NULL;
        if($this->request->getIntegerParam('task_id')) {
            $item = $this->taskFinderModel->getDetails($this->request->getIntegerParam('task_id'));
        }
        if($item != NULL) {
            $value = $this->taskMetadataModel->get($item['id'], 'sensitive_flag', FALSE);
        }
        return $this->helper->form->checkbox('sensitive_flag', t('Sensitive flag (Don\'t show in public views)'), 1, $value );
    }
}