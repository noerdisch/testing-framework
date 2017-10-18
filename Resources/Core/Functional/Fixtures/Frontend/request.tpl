<?php
require '{vendorPath}typo3/testing-framework/Classes/Core/Functional/Framework/Frontend/RequestBootstrap.php';
\Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\RequestBootstrap::setGlobalVariables({arguments});
\Noerdisch\TestingFramework\Core\Functional\Framework\Frontend\RequestBootstrap::executeAndOutput();
?>
