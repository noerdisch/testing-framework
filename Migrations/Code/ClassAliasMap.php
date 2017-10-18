<?php

return [
    // Acceptance
    '\\TYPO3\\Components\\TestingFramework\\Core\\Acceptance\\Step\\Backend\\Admin' => \Noerdisch\TestingFramework\Core\Acceptance\Step\Backend\Admin::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Acceptance\\Step\\Backend\\Editor' => \Noerdisch\TestingFramework\Core\Acceptance\Step\Backend\Editor::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Acceptance\\Support\\Helper\\ModalDialog' => \Noerdisch\TestingFramework\Core\Acceptance\Support\Helper\ModalDialog::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Acceptance\\Support\\Helper\\Topbar' => \Noerdisch\TestingFramework\Core\Acceptance\Support\Helper\Topbar::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Acceptance\\Support\\Page\\PageTree' => \Noerdisch\TestingFramework\Core\Acceptance\Support\Page\PageTree::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Acceptance\\AcceptanceCoreEnvironment' => \Noerdisch\TestingFramework\Core\Acceptance\AcceptanceCoreEnvironment::class,

    // Functional
    '\\TYPO3\\Components\\TestingFramework\\Core\\Functional\\FunctionalTestCase' => \Noerdisch\TestingFramework\Core\Functional\FunctionalTestCase::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Functional\\Framework\Frontend\\Hook\\BackendUserHandler' => \Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\Hook\BackendUserHandler::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Functional\\Framework\Frontend\\Collector' => \Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\Collector::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Functional\\Framework\Frontend\\Hook\\FrontendUserHandler' => \Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\Hook\FrontendUserHandler::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Functional\\Framework\Frontend\\Parser' => \Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\Parser::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Functional\\Framework\Frontend\\Renderer' => \Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\Renderer::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Functional\\Framework\Frontend\\RequestBootstrap' => \Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\RequestBootstrap::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Functional\\Framework\Frontend\\ResponseContent' => \Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Functional\\Framework\Frontend\\ResponseSection' => \Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\ResponseSection::class,

    // Unit
    '\\TYPO3\\Components\\TestingFramework\\Core\\Unit\\UnitTestCase' => \Noerdisch\TestingFramework\Core\Unit\UnitTestCase::class,

    // General
    '\\TYPO3\\Components\\TestingFramework\\Core\\AccessibleObjectInterface' => \Noerdisch\TestingFramework\Core\AccessibleObjectInterface::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\BaseTestCase' => \Noerdisch\TestingFramework\Core\BaseTestCase::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Exception' => \Noerdisch\TestingFramework\Core\Exception::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\FileStreamWrapper' => \Noerdisch\TestingFramework\Core\FileStreamWrapper::class,
    '\\TYPO3\\Components\\TestingFramework\\Core\\Testbase' => \Noerdisch\TestingFramework\Core\Testbase::class,

    // Fluid
    '\\TYPO3\\Components\\TestingFramework\\Fluid\\Unit\\ViewHelpers\\ViewHelperBaseTestcase' => \Noerdisch\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase::class

];
