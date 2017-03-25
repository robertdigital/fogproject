<?php
/**
 * Modifies Access control Users.
 *
 * PHP version 5
 *
 * @category AddAccessControlUser
 * @package  FOGProject
 * @author   Fernando Gietz <fernando.gietz@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Modifies Access control Users.
 *
 * @category AddAccessControlUser
 * @package  FOGProject
 * @author   Fernando Gietz <fernando.gietz@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class AddAccessControlUser extends Hook
{
    public $name = 'AddAccessControlUser';
    public $description = 'Add AccessControl to Users';
    public $active = true;
    public $node = 'accesscontrol';
    /**
     * This function modifies the header of the user page.
     * Add one column calls 'Role'
     *
     * @param mixed $arguments The arguments to modify.
     *
     * @return void
     */
    public function userTableHeader($arguments)
    {
        global $node;
        global $sub;
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if ($node != 'user') {
            return;
        }
        if ($sub == 'pending') {
            return;
        }
        foreach ((array)$arguments['headerData'] as $index => &$str) {
            if ($index == 3) {
                $arguments['headerData'][$index] = _('Role');
                $arguments['headerData'][] = $str;
            }
            unset($str);
        }
    }
    /**
     * This function modifies the data of the user page.
     * Add one column calls 'Role'
     *
     * @param mixed $arguments The arguments to modify.
     *
     * @return void
     */
    public function userData($arguments)
    {
        global $node;
        global $sub;
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if ($node != 'user') {
            return;
        }
        if ($sub == 'pending') {
            return;
        }
        foreach ((array)$arguments['attributes'] as $index => &$str) {
            if ($index == 3) {
                $arguments['attributes'][$index] = array();
                $arguments['attributes'][] = $str;
            }
            unset($str);
        }
        foreach ((array)$arguments['templates'] as $index => &$str) {
            if ($index == 3) {
                $arguments['templates'][$index] = '${role}';
                $arguments['templates'][] = $str;
            }
            unset($str);
        }
        foreach ((array)$arguments['data'] as $index => &$vals) {
            $find = array(
                'userID' => $vals['id']
            );
            $Roles = self::getSubObjectIDs(
                'AccessControlAssociation',
                $find
            );
            $cnt = count($Roles);
            if ($cnt !== 1) {
                $arguments['data'][$index]['role'] = '';
                continue;
            }
            $RoleNames = array_values(
                array_unique(
                    array_filter(
                        self::getSubObjectIDs(
                            'AccessControl',
                            array('id' => $Roles),
                            'name'
                        )
                    )
                )
            );
            $arguments['data'][$index]['role'] = $RoleNames[0];
            unset($vals);
            unset($Roles);
        }
    }
    /**
     * This function adds a new column in the result table.
     *
     * @param mixed $arguments The arguments to modify.
     *
     * @return void
     */
    public function userFields($arguments)
    {
        global $node;
        global $sub;
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        if ($node != 'user') {
            return;
        }
        $AccessControls = self::getSubObjectIDs(
            'AccessControlAssociation',
            array(
                'userID' => $arguments['User']->get('id')
            ),
            'roleID'
        );
        $cnt = self::getClass('AccessControlManager')->count(
            array(
                'id' => $AccessControls
            )
        );
        if ($cnt !== 1) {
            $acID = 0;
        } else {
            $AccessControls = self::getSubObjectIDs(
                'AccessControl',
                array('id' => $AccessControls)
            );
            $acID = $AccessControls[0];
        }
        self::arrayInsertAfter(
            _('User Name'),
            $arguments['fields'],
            _('User Access Control'),
            self::getClass('AccessControlManager')->buildSelectBox(
                $acID
            )
        );

    }
    /**
     * This function adds one entry in the roleUserAssoc table in the DB
     *
     * @param mixed $arguments The arguments to modify.
     *
     * @return void
     */
    public function userAddAccessControl($arguments)
    {
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        global $node;
        global $sub;
        global $tab;
        $subs = array(
            'add',
            'edit',
            'addPost',
            'editPost'
        );
        if ($node != 'user') {
            return;
        }
        if (!in_array($sub, $subs)) {
            return;
        }
        self::getClass('AccessControlAssociationManager')->destroy(
            array(
                'userID' => $arguments['User']->get('id')
            )
        );
        $cnt = self::getClass('AccessControlManager')
            ->count(
                array('id' => $_REQUEST['accesscontrol'])
            );
        if ($cnt !== 1) {
            return;
        }
        $Role = new AccessControl($_REQUEST['accesscontrol']);
        self::getClass('AccessControlAssociation')
            ->set('ruaUserID', $arguments['User']->get('id'))
            ->load('ruaUserID')
            ->set('ruaRoleID', $_REQUEST['accesscontrol'])
            ->set(
                'name',
                sprintf(
                    '%s-%s',
                    $Role->get('name'),
                    $arguments['User']->get('name')
                )
            )
            ->save();
    }
    /**
     * This function adds role to notes
     *
     * @param mixed $arguments The arguments to modify.
     *
     * @return void
     */
    public function addNotes($arguments)
    {
        if (!in_array($this->node, (array)$_SESSION['PluginsInstalled'])) {
            return;
        }
        global $node;
        global $sub;
        global $tab;
        if ($node != 'user') {
            return;
        }
        if (count($arguments['notes']) < 1) {
            return;
        }
        $AccessControls = self::getSubObjectIDs(
            'AccessControlAssociation',
            array(
                'userID' => $arguments['object']->get('id')
            ),
            'roleID'
        );
        $cnt = count($AccessControls);
        if ($cnt !== 1) {
            $acID = 0;
        } else {
            $AccessControls = array_values(
                array_unique(
                    array_filter(
                        self::getSubObjectIDs(
                            'AccessControl',
                            array('id' => $AccessControls),
                            'name'
                        )
                    )
                )
            );
            $acID = $AccessControls[0];
        }
        $arguments['notes'][_('Role')] = $acID;
    }
}
$AddAccessControlUser = new AddAccessControlUser();
$HookManager
    ->register(
        'USER_HEADER_DATA',
        array(
            $AddAccessControlUser,
            'userTableHeader'
        )
    )
    ->register(
        'USER_DATA',
        array(
            $AddAccessControlUser,
            'userData'
        )
    )
    ->register(
        'USER_FIELDS',
        array(
            $AddAccessControlUser,
            'userFields'
        )
    )
    ->register(
        'USER_ADD_SUCCESS',
        array(
            $AddAccessControlUser,
            'userAddAccessControl'
        )
    )
    ->register(
        'USER_UPDATE_SUCCESS',
        array(
            $AddAccessControlUser,
            'userAddAccessControl'
        )
    )
    ->register(
        'SUB_MENULINK_DATA',
        array(
            $AddAccessControlUser,
            'addNotes'
        )
    );
