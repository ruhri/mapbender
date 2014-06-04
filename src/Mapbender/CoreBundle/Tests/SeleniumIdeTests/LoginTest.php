<?php

namespace Mapbender\CoreBundle\Tests\SeleniumIdeTests;

class LoginTest extends \PHPUnit_Extensions_SeleniumTestCase
{
  protected function setUp()
  {
      $this->setHost('localhost');
      $this->setPort(4445);
      $this->setBrowser("*chrome");
      $this->getDriver(array('name' => '*chrome', 'host' => 'localhost', 'port' => 4445))->setWebDriverCapabilities(array('tunnel-identifier' => getenv('TRAVIS_JOB_NUMBER')));
      $this->setBrowserUrl('http://' . TEST_WEB_SERVER_HOST . ':' . TEST_WEB_SERVER_PORT . '/app_dev.php/');
  }

  public function testMyTestCase()
  {
    $this->open("/");
    $this->click("//a[contains(@href, '/user/login')]");
    $this->waitForPageToLoad("3000");
    $this->type("id=username", "root");
    $this->type("id=password", "root");
    $this->click("css=input.right.button");
    $this->waitForPageToLoad("3000");
    for ($second = 0; ; $second++) {
        if ($second >= 60) $this->fail("timeout");
        try {
            if ($this->isElementPresent("//*[@id=\"accountMenu\"]")) break;
        } catch (Exception $e) {}
        sleep(1);
    }
  }
}
?>
