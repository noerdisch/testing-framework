<?php

namespace Noerdisch\TestingFramework\Tests\Acceptance\Backend\Extensionmanager;

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

use Codeception\Scenario;
use Noerdisch\TestingFramework\Core\Acceptance\Step\Backend\Admin;

/**
 * Tests for the "Install list view" of the extension manager
 */
class InstalledExtensionsCest
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
        $I->click('Extensions', '#typo3-menu');

        // switch to content iframe
        $I->switchToIFrame('content');
        $I->waitForElementVisible('#typo3-extension-list');
    }

    /**
     * @param Admin $I
     */
    public function checkSearchFiltersList(Admin $I)
    {
        $I->canSeeNumberOfElements('#typo3-extension-list tbody tr[role="row"]', [10, 100]);

        // Fill extension search field
        $I->fillField('Tx_Extensionmanager_extensionkey', 'cshmanual');

        // see 2 rows. 1 for the header and one for the result
        $I->canSeeNumberOfElements('#typo3-extension-list tbody tr[role="row"]', 1);

        // Look for extension key
        $I->canSee('cshmanual', '#typo3-extension-list tbody tr[role="row"] td');

        // unset the filter
        $I->waitForElementVisible('#Tx_Extensionmanager_extensionkey ~button.close', 1);
        $I->click('#Tx_Extensionmanager_extensionkey ~button.close');

        $I->canSeeNumberOfElements('#typo3-extension-list tbody tr[role="row"]', [10, 100]);
    }

    /**
     * @param Admin $I
     * @param Scenario $scenario
     */
    public function checkIfUploadFormAppears(Admin $I, Scenario $scenario)
    {
        if ($I->isComposerMode()) {
            $scenario->incomplete('Skip check if "' . __METHOD__ . '", because we are in composer mode');
        } else {
            $I->cantSeeElement('.module-body .uploadForm');
            $I->click('a[title="Upload Extension .t3x/.zip"]', '.module-docheader');
            $I->seeElement('.module-body .uploadForm');
        }
    }

    /**
     * @param Admin $I
     */
    public function checkUninstallingAndInstallingAnExtension(Admin $I)
    {
        $I->wantTo('Check if uninstalling and installing an extension with backend module removes and adds the module from the module menu.');
        $I->amGoingTo('uninstall extension belog');
        $I->switchToIFrame();
        $I->canSeeElement('#system_BelogLog');

        $I->switchToIFrame('content');
        $I->fillField('Tx_Extensionmanager_extensionkey', 'belog');
        $I->waitForElementVisible('//*[@id="typo3-extension-list"]/tbody/tr[@id="belog"]');
        $I->click('a[data-original-title="Deactivate"]', '//*[@id="typo3-extension-list"]/tbody/tr[@id="belog"]');

        $I->waitForElementVisible('#Tx_Extensionmanager_extensionkey ~button.close', 1);
        $I->click('#Tx_Extensionmanager_extensionkey ~button.close');

        $I->switchToIFrame();
        $I->cantSeeElement('#system_BelogLog');

        $I->amGoingTo('install extension belog');
        $I->switchToIFrame();
        $I->canSeeElement('.typo3-module-menu-item-link');
        $I->cantSeeElement('#system_BelogLog');

        $I->switchToIFrame('content');
        $I->fillField('Tx_Extensionmanager_extensionkey', 'belog');
        $I->waitForElementVisible('//*[@id="typo3-extension-list"]/tbody/tr[@id="belog"]');
        $I->click('a[data-original-title="Activate"]', '//*[@id="typo3-extension-list"]/tbody/tr[@id="belog"]');

        $I->waitForElementVisible('#Tx_Extensionmanager_extensionkey ~button.close', 1);
        $I->click('#Tx_Extensionmanager_extensionkey ~button.close');

        $I->switchToIFrame();
        $I->canSeeElement('#system_BelogLog');
    }
}
