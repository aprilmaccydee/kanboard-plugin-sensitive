<?php

namespace Kanboard\Plugin\SensitiveItem;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;
use Kanboard\Model\TaskMetadataModel;
use Kanboard\Model\TaskModel;
use JsonRPC\Exception\AuthenticationFailureException;
use Kanboard\Core\Filter\QueryBuilder;
use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Plugin\SensitiveItem\Model\FilteredTaskFinderModel;



class Plugin extends Base
{
    public function initialize()
    {
        // Add helper for form
        $this->helper->register('sensitiveItemHelper', '\Kanboard\Plugin\SensitiveItem\Helper\SensitiveItemHelper');
        // Add checkbox to first column of task form
        $this->template->hook->attach('template:task:form:first-column', 'SensitiveItem:task_creation/sensitive');

        $sensitiveFlag = FALSE;
        // Task Creation: Get sensitive flag value / unset for db
        $this->hook->on('model:task:creation:prepare', function(&$values) use(&$sensitiveFlag) {
            $sensitiveFlag = $values['sensitive_flag'];
            unset($values['sensitive_flag']);
        });
        // Task Creation: We now have a task ID, set the sensitive flag.
        $this->hook->on('model:task:creation:aftersave', function($taskId) use(&$sensitiveFlag) {
            $this->taskMetadataModel->save($taskId, array('sensitive_flag' => $sensitiveFlag));
        });

        // Task Update: Set sensitive flag
        $this->hook->on('model:task:modification:prepare', function(&$values) {
            $task = $this->taskFinderModel->getDetails($this->request->getIntegerParam('task_id'));
            $this->taskMetadataModel->save($task['id'], array('sensitive_flag' => $values['sensitive_flag'] == 1 ? true : false));
            unset($values['sensitive_flag']);
        });

        // For public board view, exclude with sensitive flag
        $this->hook->on('formatter:board:query', function (\PicoDb\Table &$query) {
            if($this->router->getController() == "BoardViewController" && $this->router->getAction() == "readonly") {
                $query->subquery("SELECT `value` FROM `task_has_metadata` WHERE `task_id` = " . TaskModel::TABLE . ".id AND `name` = 'sensitive_flag'", "sensitive");
                $query->addCondition('(sensitive <> 1 OR sensitive IS NULL)');
            }
        });

        $this->on('app.bootstrap', function($container) {
            // Exclude sensitive tasks from project public feed
            if($this->router->getController() == "FeedController" && $this->router->getAction() == "project") {
                $container['projectActivityQuery'] = $container->factory(function ($c) {
                    $builder = new QueryBuilder();
                    $builder->withQuery($c['projectActivityModel']->getQuery());
                    $builder->getQuery()->subquery("SELECT `value` FROM `task_has_metadata` WHERE `task_id` = `project_activities`.task_id AND `name` = 'sensitive_flag'", "sensitive");
                    $builder->getQuery()->addCondition('(sensitive <> 1 OR sensitive IS NULL)');
                    return $builder;
                });
            }
            // Exclude sensitive tasks from iCal feed
            if($this->router->getController() == "ICalendarController" && $this->router->getAction() == "project") {
                $container['taskFinderModel'] = $container->factory(function($c) {
                    return new FilteredTaskFinderModel($c);
                });
            }
            // Exclude sensitive tasks from being viewed by URL
            if($this->router->getController() == "TaskViewController" && $this->router->getAction() == "readonly") {
                $taskId = $this->request->getIntegerParam('task_id');
                $value = $this->taskMetadataModel->get($taskId, 'sensitive_flag', FALSE);
                if($value) {
                    throw AccessForbiddenException::getInstance()->withoutLayout();
                }
            }
        });
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getPluginName()
    {
        return 'Sensitive Item';
    }

    public function getPluginDescription()
    {
        return t('Allows hiding certain tasks from the public project feeds.');
    }

    public function getPluginAuthor()
    {
        return 'April MacDonald';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/kanboard/plugin-myplugin';
    }
}

