<?php

namespace Noerdisch\TestingFramework\Tests\Acceptance\Backend\BackendUser;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Noerdisch\TestingFramework\Core\Acceptance\Step\Backend\Admin;
use Codeception\Util\Locator;

/**
 * List User tests
 */
class ListUserCest
{
    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->login();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions

        $I->switchToIFrame();
        $I->waitForElementVisible('#typo3-menu');
        $I->see('Backend users');
        $I->click('Backend users');

        // switch to content iframe
        $I->switchToIFrame('content');
        $I->waitForText('Backend User Listing');
    }

    /**
     * @param Admin $I
     */
    public function showsHeadingAndListsBackendUsers(Admin $I)
    {
        $I->see('Backend User Listing');

        $I->wantTo('See the table of users');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact four Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 4);
    }

    /**
     * @param Admin $I
     */
    public function filterUsersByUsername(Admin $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('table.table-striped');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('table tbody tr', $this->getCountOfUsers(4));

        $I->wantTo('Filter the list of user by valid username admin');
        $I->fillField('#tx_Beuser_username', 'admin');
        $I->click('Filter');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact one fitting Backend User created from the Fixtures
        $this->checkCountOfUsers($I, 1);

        $I->wantTo('Filter the list of user by valid username administrator');
        $I->fillField('#tx_Beuser_username', 'administrator');
        $I->click('Filter');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact no fitting Backend User created from the Fixtures
        $this->checkCountOfUsers($I, 0);
    }

    /**
     * @param Admin $I
     */
    public function filterUsersByAdmin(Admin $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('table.table-striped');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('table.table-striped  tbody tr', $this->getCountOfUsers(4));

        $I->wantToTest('Filter BackendUser and see only admins');
        $I->selectOption('#tx_Beuser_usertype', 'Admin only');
        $I->click('Filter');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);

        $I->wantToTest('Filter BackendUser and see normal users');
        $I->selectOption('#tx_Beuser_usertype', 'Normal users only');
        $I->click('Filter');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);
    }

    /**
     * @param Admin $I
     */
    public function filterUsersByStatus(Admin $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('table.table-striped');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('table.table-striped tbody tr', $this->getCountOfUsers(4));

        $I->wantToTest('Filter BackendUser and see only active users');
        $I->selectOption('#tx_Beuser_status', 'Active only');
        $I->click('Filter');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);

        $I->wantToTest('Filter BackendUser and see only inactive users');
        $I->selectOption('#tx_Beuser_status', 'Inactive only');
        $I->click('Filter');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);
    }

    /**
     * @param Admin $I
     */
    public function filterUsersByLogin(Admin $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('table.table-striped');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('table.table-striped tbody tr', $this->getCountOfUsers(4));

        $I->wantToTest('Filter BackendUser and see only users logged in before');
        $I->selectOption('#tx_Beuser_logins', 'Logged in before');
        $I->click('Filter');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);

        $I->wantToTest('Filter BackendUser and see only users never logged in before');
        $I->selectOption('#tx_Beuser_logins', 'Never logged in');
        $I->click('Filter');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact two fitting Backend Users created from the Fixtures
        $this->checkCountOfUsers($I, 2);
    }

    /**
     * @param Admin $I
     */
    public function filterUsersByUserGroup(Admin $I)
    {
        $I->wantTo('See the table of users');
        $I->waitForElementVisible('table.table-striped');
        // We expect exact four Backend Users created from the Fixtures
        $I->canSeeNumberOfElements('table.table-striped  tbody tr', $this->getCountOfUsers(4));

        // We expect exact one Backend Users created from the Fixtures has the usergroup named 'editor-group'
        $I->wantToTest('Filter BackendUser and see only users with given usergroup');
        $I->selectOption('#tx_beuser_backendUserGroup', 'editor-group');
        $I->click('Filter');
        $I->waitForElementVisible('table.table-striped');

        // We expect exact one fitting Backend User created from the Fixtures
        $this->checkCountOfUsers($I, 1);
    }

    /**
     * @param Admin $I
     * @param int $countOfUsers
     */
    protected function checkCountOfUsers(Admin $I, $countOfUsers)
    {
        $I->canSeeNumberOfElements('table tbody tr', $this->getCountOfUsers($countOfUsers));
        $I->wantToTest('If a number of users is shown in the footer row');
        $I->canSeeNumberOfElements(Locator::lastElement('table tbody tr'), 1);
        $I->see($countOfUsers . ' Users', Locator::lastElement('table tbody tr'));
    }

    /**
     * We need to increment the number of users because the number of tr elements are including a footer.
     * In TYPO3 version 8 and higher the markup has been changed so that the footer row is a separate tfoot row.
     *
     * @param int $amount
     * @return int
     */
    protected function getCountOfUsers($amount)
    {
        return $amount + 1;
    }
}
