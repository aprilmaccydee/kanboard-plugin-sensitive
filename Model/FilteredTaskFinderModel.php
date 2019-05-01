<?php

namespace Kanboard\Plugin\SensitiveItem\Model;

use Kanboard\Model\TaskFinderModel;
use Kanboard\Model\TaskModel;
use Kanboard\Model\UserModel;

class FilteredTaskFinderModel extends TaskFinderModel
{
    public function getICalQuery()
    {
        $query = $this->db->table(TaskModel::TABLE)
            ->left(UserModel::TABLE, 'ua', 'id', TaskModel::TABLE, 'owner_id')
            ->left(UserModel::TABLE, 'uc', 'id', TaskModel::TABLE, 'creator_id')
            ->columns(
                TaskModel::TABLE.'.*',
                'ua.email AS assignee_email',
                'ua.name AS assignee_name',
                'ua.username AS assignee_username',
                'uc.email AS creator_email',
                'uc.name AS creator_name',
                'uc.username AS creator_username'
            );
        $query->subquery('SELECT `value` FROM `task_has_metadata` WHERE `task_id` = `tasks`.id AND `name` = \'sensitive_flag\'', "sensitive");
        $query->addCondition('(sensitive <> 1 OR sensitive IS NULL)');
        return $query;
    }

}