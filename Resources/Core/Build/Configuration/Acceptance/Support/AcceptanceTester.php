<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * The session cookie that is used if the session is injected.
     * This session must exist in the database fixture to get a logged in state.
     *
     * @var string
     */
    protected $sessionCookie = '';

    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var string
     */
    protected $password = '';

    /**
     * Use the existing database session from the fixture by setting the backend user cookie
     */
    public function useExistingSession()
    {
        $I = $this;
        $I->amOnPage('/typo3/index.php');

        // @todo: There is a bug in PhantomJS / firefox (?) where adding a cookie fails.
        // This bug will be fixed in the next PhantomJS version but i also found
        // this workaround. First reset / delete the cookie and than set it and catch
        // the webdriver exception as the cookie has been set successful.
        try {
            $I->resetCookie('be_typo_user');
            $I->setCookie('be_typo_user', $this->sessionCookie);
        } catch (\Facebook\WebDriver\Exception\UnableToSetCookieException $e) {
        }
        try {
            $I->resetCookie('be_lastLoginProvider');
            $I->setCookie('be_lastLoginProvider', '1433416747');
        } catch (\Facebook\WebDriver\Exception\UnableToSetCookieException $e) {
        }

        // reload the page to have a logged in backend
        $I->amOnPage('/typo3/index.php');
    }

    /**
     * Helper method for user login on backend login screen
     */
    public function login()
    {
        $I = $this;

        // if snapshot exists - skipping login
        if ($I->loadSessionSnapshot('login')) {
            return;
        }

        $I->amOnPage('/typo3/index.php');
        $I->waitForElement('#t3-username');
        $I->fillField('#t3-username', $this->username);
        $I->fillField('#t3-password', $this->password);
        $I->click('#t3-login-submit-section > button');
        $I->waitForElement('.nav', 5);

        $I->saveSessionSnapshot('login');
    }

    /**
     * Some tests not working when you TYPO3 instance is in composer mode.
     * So we test this here and return a bool.
     *
     * Should be used for ExtensionManager related Cests.
     *
     * @return bool
     */
    public function isComposerMode()
    {
        $I = $this;

        try {
            $I->canSee('Composer mode');
        } catch (PHPUnit_Framework_AssertionFailedError $f) {
            return false;
        }
        return true;
    }
}
