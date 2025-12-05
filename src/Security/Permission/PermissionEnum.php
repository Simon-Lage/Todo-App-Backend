<?php

declare(strict_types=1);

namespace App\Security\Permission;

enum PermissionEnum: string
{
    case CAN_CREATE_USER = 'perm_can_create_user';
    case CAN_EDIT_USER = 'perm_can_edit_user';
    case CAN_READ_USER = 'perm_can_read_user';
    case CAN_DELETE_USER = 'perm_can_delete_user';

    case CAN_CREATE_ROLES = 'perm_can_create_roles';
    case CAN_EDIT_ROLES = 'perm_can_edit_roles';
    case CAN_READ_ROLES = 'perm_can_read_roles';
    case CAN_DELETE_ROLES = 'perm_can_delete_roles';

    case CAN_CREATE_TASKS = 'perm_can_create_tasks';
    case CAN_EDIT_TASKS = 'perm_can_edit_tasks';
    case CAN_READ_ALL_TASKS = 'perm_can_read_all_tasks';
    case CAN_DELETE_TASKS = 'perm_can_delete_tasks';
    case CAN_ASSIGN_TASKS_TO_USER = 'perm_can_assign_tasks_to_user';
    case CAN_ASSIGN_TASKS_TO_PROJECT = 'perm_can_assign_tasks_to_project';

    case CAN_CREATE_PROJECTS = 'perm_can_create_projects';
    case CAN_EDIT_PROJECTS = 'perm_can_edit_projects';
    case CAN_READ_PROJECTS = 'perm_can_read_projects';
    case CAN_DELETE_PROJECTS = 'perm_can_delete_projects';
}

