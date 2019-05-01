<?php

namespace Kanboard\Plugin\SensitiveItem\Model;

use Kanboard\Model\TaskLinkModel;
use Kanboard\Model\TaskModel;
use Kanboard\Model\UserModel;
use Kanboard\Model\ColumnModel;
use Kanboard\Model\ProjectModel;
use Kanboard\Model\LinkModel;

class FilteredTaskLinkModel extends TaskLinkModel
{
    public function getFiltered($task_id)
    {
        return $this->db
        ->table(self::TABLE)
        ->columns(
            self::TABLE.'.id',
            self::TABLE.'.opposite_task_id AS task_id',
            LinkModel::TABLE.'.label',
            TaskModel::TABLE.'.title',
            TaskModel::TABLE.'.is_active',
            TaskModel::TABLE.'.project_id',
            TaskModel::TABLE.'.column_id',
            TaskModel::TABLE.'.color_id',
            TaskModel::TABLE.'.date_completed',
            TaskModel::TABLE.'.date_started',
            TaskModel::TABLE.'.date_due',
            TaskModel::TABLE.'.time_spent AS task_time_spent',
            TaskModel::TABLE.'.time_estimated AS task_time_estimated',
            TaskModel::TABLE.'.owner_id AS task_assignee_id',
            UserModel::TABLE.'.username AS task_assignee_username',
            UserModel::TABLE.'.name AS task_assignee_name',
            ColumnModel::TABLE.'.title AS column_title',
            ProjectModel::TABLE.'.name AS project_name'
        )
        ->eq(self::TABLE.'.task_id', $task_id)
        ->subquery("SELECT `value` FROM `task_has_metadata` WHERE `task_id` = `task_has_links`.opposite_task_id AND `name` = 'sensitive_flag'", "sensitive")
        ->addCondition('(sensitive <> 1 OR sensitive IS NULL)')
        ->join(LinkModel::TABLE, 'id', 'link_id')
        ->join(TaskModel::TABLE, 'id', 'opposite_task_id')
        ->join(ColumnModel::TABLE, 'id', 'column_id', TaskModel::TABLE)
        ->join(UserModel::TABLE, 'id', 'owner_id', TaskModel::TABLE)
        ->join(ProjectModel::TABLE, 'id', 'project_id', TaskModel::TABLE)
        ->asc(LinkModel::TABLE.'.id')
        ->desc(ColumnModel::TABLE.'.position')
        ->desc(TaskModel::TABLE.'.is_active')
        ->asc(TaskModel::TABLE.'.position')
        ->asc(TaskModel::TABLE.'.id')
        ->findAll();
    }
    public function getAllGroupedByLabel($task_id)
    {
        $links = $this->getFiltered($task_id);
        $result = array();

        foreach ($links as $link) {
            if (! isset($result[$link['label']])) {
                $result[$link['label']] = array();
            }

            $result[$link['label']][] = $link;
        }

        return $result;
    }
}