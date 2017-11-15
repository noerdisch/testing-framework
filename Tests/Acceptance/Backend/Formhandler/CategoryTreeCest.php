<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Formhandler;

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

use Codeception\Util\Locator;
use Noerdisch\TestingFramework\Core\Acceptance\Step\Backend\Admin;

/**
 * Category tree tests
 */
class CategoryTreeCest
{
    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->login();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('content');
        $I->waitForText('New TYPO3 site');
        $I->switchToIFrame();
    }

    /**
     * @param Admin $I
     */
    public function _after(Admin $I)
    {
        $I->switchToIFrame();
    }

    /**
     * @param Admin $I
     */
    public function checkIfCategoryListIsAvailable(Admin $I)
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('#web .typo3-module-menu-group-container .typo3-module-menu-item');
        $I->click('#web_list');
        $I->switchToIFrame('content');
        $I->waitForElement('#recordlist-sys_category');
        $I->seeNumberOfElements('#recordlist-sys_category table > tbody > tr', [5, 100]);
    }

    /**
     * @param Admin $I
     */
    public function editCategoryItem(Admin $I)
    {
        // A sub-element of web module is show
        $I->waitForElementVisible('#web .typo3-module-menu-group-container .typo3-module-menu-item');
        $I->click('#web_list');
        $I->switchToIFrame('content');
        // Collapse all tables and expand category again - ensures category fits into window
        $I->click('.icon-actions-view-list-collapse');
        //$I->executeJS('$(\'.icon-actions-view-list-collapse\').click();');
        $I->wait(1);
        $I->click('.icon-actions-view-list-expand');
        //$I->executeJS('$(\'a[data-table="sys_category"] .icon-actions-view-list-expand\').click();');
        $I->wait(1);
        // Select category with id 7
        $I->click('#recordlist-sys_category tr[data-uid="7"] a[data-original-title="Edit record"]');
        // Change title and level to root
        $I->fillField('input[data-formengine-input-name="data[sys_category][7][title]"]', 'level-1-4');
        $I->click('//*[@id="ext-gen7"]/div/li/ul/li[1]/ul/li/ul/li[1]/div/input');
        $I->click('//*[@id="ext-gen7"]/div/li/ul/li[1]/ul/li/ul/li[3]/div/input');
        $I->click('button[name="_savedok"]');
        // Wait for tree and check if isset level-1-4
        $I->waitForElement('.form-section .x-tree-root-ct');
        $I->see('level-1-4', '.form-section .x-tree-root-ct span');
    }
}
