<?php

/**
 * ACL - permissions business logic
 *
 * TERMINOLOGY:
 *      Admin   - internal admin
 *      Main    - main user of the account (the oldest; has null in users.created_by)
 *      Regular - normal account user that has been created by someone (users.created_by not null)
 */
class Model_User_Permissions_BusinessLogic
{
    /** @var Model_Propel_User  */
    protected $loggedUser;

    /** @var bool  */
    protected $loggedIsAdmin;

    /** @var bool  */
    protected $loggedIsMain;

    /**
     * Model_User_Permissions_BusinessLogic constructor.
     * @param Model_Propel_User $loggedInUser
     * @param $loggedInRole
     */
    public function __construct(Model_Propel_User $loggedInUser, $loggedInRole = '')
    {
        $this->loggedUser = $loggedInUser;
        $this->loggedIsAdmin = ($loggedInRole == Model_Const::ROLE_ADMIN);
        $this->loggedIsMain = $this->userIsMain($this->loggedUser);
    }

    /**
     * @param Model_Propel_User $propelUser
     * @return bool
     */
    public function userIsMain(Model_Propel_User $propelUser)
    {
        return !$propelUser->getCreatedBy();
    }

    public function isFromSameAccount(Model_Propel_User $propelUser)
    {
        $loggedAccountId = $this->loggedUser->getAccountUsers()->getFirst()->getAccountId();
        $testedAccountId = $propelUser->getAccountUsers()->getFirst()->getAccountId();

        return $loggedAccountId == $testedAccountId;
    }

    /**
     * EDIT permission:
     *
     * If the logged in user is main user (the account oldest) OR if the logged in user is a internal admin
     *    Allow edit of all account's users
     *
     * If the logged in user is a normal account (regular) user
     *    Disable edit for the main user
     *    Enable edit for all other users
     *
     * @param Model_Propel_User $propelUser
     * @return bool
     */
    public function canEditUser(Model_Propel_User $propelUser)
    {
        if (!$this->isFromSameAccount($propelUser)) {
            return false;
        }

        return $this->loggedIsAdmin
            || $this->loggedIsMain
            || (!$this->loggedIsAdmin && !$this->loggedIsMain && !$this->userIsMain($propelUser));
    }

    /**
     * DELETE permission:
     *
     * If the logged in user is main user (the account oldest) OR if the logged in user is a internal admin
     *    Disable delete for the main user
     *
     * If the logged in user is a normal account (regular) user
     *    Disable delete for the main user
     *    Disable the delete for the logged in user
     *    Enable edit for all other users
     *
     * @param Model_Propel_User $propelUser
     * @return bool
     */
    public function canDeleteUser(Model_Propel_User $propelUser)
    {
        if (!$this->isFromSameAccount($propelUser)) {
            return false;
        }

        return !$this->userIsMain($propelUser) && ($propelUser->getUserId() != $this->loggedUser->getUserId());
    }
}
