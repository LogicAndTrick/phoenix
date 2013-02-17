<?php

class User extends Model
{
    public $table = 'Users';
    public $columns = array(
        'ID' => 'ID',
        //'Level' => 'Level', // For Level Authorisation
        //'RoleID' => 'RoleID', // For Role Authorisation (see below)
        'Name' => 'Name',
        'Password' => 'Password',
        'OpenID' => 'OpenID',
        'Email' => 'Email',
        'Cookie' => 'Cookie',
        'NumLogins' => 'NumLogins',
        'LastLogin' => 'LastLogin',
        'LastAccess' => 'LastAccess',
        'LastPage' => 'LastPage',
        'IP' => 'IP',
        'Unlock' => 'Unlock'
    );
    public $alias = array();
    public $primaryKey = 'ID';
    public $one = array(
        // 'Role' => array('RoleID' => 'ID') // For Role Authorisation
    );
    public $many = array(
        // 'UserPermission' => array('ID' => 'UserID') // For Permission Authorisation (see below)
    );
    public $validation = array(
        'Name' => array('required', 'db-unique'),
        'Password' => array('required'),
        // 'OpenID' => array('required') // Depending on your auth setup, change validation as needed
    );
    public $mappings = array();
}

/*
 * When using Permission based authorisation (refer to the documentation for more info):
 *  1. Uncomment the UserPermission relationship in $many
 *  2. Create a UserPermission model with $one relationships to both User and Permission
 *  3. Create a Permission model with a $many relationship to UserPermission
 *  4. Configure the PermissionAuthorisation class with the model, join and field names
 *
 * When using Role based authorisation (again, refer to documentation for more info):
 *  1. Uncomment the RoleID field and the Role relationship in $one
 *  2. Create a Role model with a $many relationship to User
 *  3. Configure the RoleAuthorisation class with the model and field names
 */