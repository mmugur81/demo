<?php

/**
 * Test for Model_User_Permissions_BusinessLogic
 *
 * TERMINOLOGY:
 *      Admin   - internal admin
 *      Main    - main user of the account (the oldest; has null in users.created_by)
 *      Regular - normal account user that has been created by someone (users.created_by not null)
 *
 * EDIT permission:
 *
 * If the logged in user is main user (the account oldest) OR if the logged in user is a internal admin
 *    Allow edit of all account's users
 *
 * If the logged in user is a normal account (regular) user
 *    Disable edit for the main user
 *    Enable edit for all other users
 *
 * DELETE permission:
 *
 * If the logged in user is main user (the account oldest) OR if the logged in user is a internal admin
 *    Disable delete for the main user
 *
 * If the logged in user is a normal account (regular) user
 *    Disable delete for the main user
 *    Disable the delete for the logged in user
 *    Enable edit for all other users
 */
class Model_User_Permissions_BusinessLogicTest extends Ticketscript_Testcase
{
    /** @var  Model_Propel_User */
    protected $admin;

    /** @var  Model_Propel_User */
    protected $main;

    /** @var  Model_Propel_User */
    protected $regular1;

    /** @var  Model_Propel_User */
    protected $regular2;

    /** @var  Model_Propel_User */
    protected $other;

    public function setUp()
    {
        // Create different kinds of users
        $this->admin = new Model_Propel_User();
        $this->admin->setUserId(100);
        $this->setAccountIdForUser($this->admin, 1000);

        $this->main = new Model_Propel_User();
        $this->main->setUserId(200);
        $this->setAccountIdForUser($this->main, 1000);

        $this->regular1 = new Model_Propel_User();
        $this->regular1->setUserId(300);
        $this->regular1->setCreatedBy(200);
        $this->setAccountIdForUser($this->regular1, 1000);

        $this->regular2 = new Model_Propel_User();
        $this->regular2->setUserId(400);
        $this->regular2->setCreatedBy(200);
        $this->setAccountIdForUser($this->regular2, 1000);

        // Regular user from another account
        $this->other = new Model_Propel_User();
        $this->other->setUserId(400);
        $this->other->setCreatedBy(200);
        $this->setAccountIdForUser($this->other, 2000);
    }

    protected function setAccountIdForUser(Model_Propel_User $user, $accountId)
    {
        $accountUser = new Model_Propel_AccountUser();
        $accountUser->setUserId($user->getUserId());
        $accountUser->setAccountId($accountId);
        $user->addAccountUser($accountUser);
    }

    /***** Permission: EDIT *******************************************************************************************/

    public function testAdminCanEditMain()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->admin, Model_Const::ROLE_ADMIN);
        $this->assertTrue($bl->canEditUser($this->main));
    }

    public function testAdminCanEditRegular()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->admin, Model_Const::ROLE_ADMIN);
        $this->assertTrue($bl->canEditUser($this->regular1));
    }

    public function testMainCanEditMain()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->main);
        $this->assertTrue($bl->canEditUser($this->main));
    }

    public function testMainCanEditRegular()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->main);
        $this->assertTrue($bl->canEditUser($this->regular1));
    }

    public function testRegularCanNotEditMain()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->regular1);
        $this->assertFalse($bl->canEditUser($this->main));
    }

    public function testRegularCanEditSelf()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->regular1);
        $this->assertTrue($bl->canEditUser($this->regular1));
    }

    public function testRegularCanEditOtherRegular()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->regular1);
        $this->assertTrue($bl->canEditUser($this->regular2));
    }

    public function testCanNotEditOtherAccount()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->regular1);
        $this->assertFalse($bl->canEditUser($this->other));
    }

    /***** Permission: DELETE *****************************************************************************************/

    public function testAdminCanNotDeleteMain()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->admin, Model_Const::ROLE_ADMIN);
        $this->assertFalse($bl->canDeleteUser($this->main));
    }

    public function testAdminCanDeleteRegular()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->admin, Model_Const::ROLE_ADMIN);
        $this->assertTrue($bl->canDeleteUser($this->regular1));
    }

    public function testMainCanNotDeleteMain()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->main);
        $this->assertFalse($bl->canDeleteUser($this->main));
    }

    public function testMainCanDeleteRegular()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->main);
        $this->assertTrue($bl->canDeleteUser($this->regular1));
    }

    public function testRegularCanNotDeleteMain()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->regular1);
        $this->assertFalse($bl->canDeleteUser($this->main));
    }

    public function testRegularCanNotDeleteSelf()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->regular1);
        $this->assertFalse($bl->canDeleteUser($this->regular1));
    }

    public function testRegularCanDeleteOtherRegular()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->regular1);
        $this->assertTrue($bl->canDeleteUser($this->regular2));
    }

    public function testCanNotDeleteOtherAccount()
    {
        $bl = new Model_User_Permissions_BusinessLogic($this->regular1);
        $this->assertFalse($bl->canDeleteUser($this->other));
    }
}
