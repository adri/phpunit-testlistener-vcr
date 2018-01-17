<?php

namespace VCR\PHPUnit\TestListener;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use VCR\Configuration;
use VCR\VCR;

/**
 * A TestListener that integrates with PHP-VCR.
 *
 * Here is an example XML configuration for activating this listener:
 *
 * <code>
 * <listeners>
 *   <listener class="VCR\PHPUnit\TestListener\VCRTestListener" file="vendor/php-vcr/phpunit-testlistener-vcr/src/VCRTestListener.php" />
 * </listeners>
 * </code>
 *
 * @author    Adrian Philipp <mail@adrian-philipp.com>
 * @author    Davide Borsatto <davide.borsatto@gmail.com>
 * @copyright 2011-2017 Adrian Philipp <mail@adrian-philipp.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 *
 * @version   Release: @package_version@
 *
 * @see       http://www.phpunit.de/
 */
class VCRTestListener implements TestListener
{
    /**
     * @var array
     */
    protected $runs = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var int
     */
    protected $suites = 0;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    /**
     * An error occurred.
     *
     * @param Test      $test
     * @param Exception $e
     * @param float     $time
     */
    public function addError(Test $test, \Exception $e, $time)
    {
    }

    /**
     * A warning occurred.
     *
     * @param Test    $test
     * @param Warning $e
     * @param float   $time
     *
     * @since Method available since Release 5.1.0
     */
    public function addWarning(Test $test, Warning $e, $time)
    {
    }

    /**
     * A failure occurred.
     *
     * @param Test                 $test
     * @param AssertionFailedError $e
     * @param float                $time
     */
    public function addFailure(Test $test, AssertionFailedError $e, $time)
    {
    }

    /**
     * Incomplete test.
     *
     * @param Test       $test
     * @param \Exception $e
     * @param float      $time
     */
    public function addIncompleteTest(Test $test, \Exception $e, $time)
    {
    }

    /**
     * Skipped test.
     *
     * @param Test       $test
     * @param \Exception $e
     * @param float      $time
     */
    public function addSkippedTest(Test $test, \Exception $e, $time)
    {
    }

    /**
     * Risky test.
     *
     * @param Test       $test
     * @param \Exception $e
     * @param float      $time
     */
    public function addRiskyTest(Test $test, \Exception $e, $time)
    {
    }

    /**
     * A test started.
     *
     * @param Test $test
     *
     * @return bool|null
     */
    public function startTest(Test $test)
    {
        $class = get_class($test);
        $method = $test->getName(false);

        if (!method_exists($class, $method)) {
            return;
        }

        $reflection = new \ReflectionMethod($class, $method);
        $docBlock = $reflection->getDocComment();

        // Use regex to parse the doc_block for a specific annotation
        $parsed = self::parseDocBlock($docBlock, '@vcr');
        $cassetteName = array_pop($parsed);

        $configuration = VCR::configure();
        foreach ($this->options as $option => $value) {
            self::configure($configuration, $option, $value);
        }

        // If the cassette name ends in .json, then use the JSON storage format
        if (substr($cassetteName, '-5') == '.json') {
            VCR::configure()->setStorage('json');
        }

        if (empty($cassetteName)) {
            return true;
        }

        VCR::turnOn();
        VCR::insertCassette($cassetteName);
    }

    private static function parseDocBlock($docBlock, $tag)
    {
        $matches = array();

        if (empty($docBlock)) {
            return $matches;
        }

        $regex = "/{$tag} (.*)(\\r\\n|\\r|\\n)/U";
        preg_match_all($regex, $docBlock, $matches);

        if (empty($matches[1])) {
            return array();
        }

        // Removed extra index
        $matches = $matches[1];

        // Trim the results, array item by array item
        foreach ($matches as $ix => $match) {
            $matches[$ix] = trim($match);
        }

        return $matches;
    }

    private static function configure(Configuration $configuration, $option, $value)
    {
        switch ($option) {
            case 'mode':
                $configuration->setMode($value);
                break;
            case 'cassettePath':
                $configuration->setCassettePath($value);
                break;
            case 'requestMatchers':
                $configuration->enableRequestMatchers($value);
                break;
            case 'whiteList':
                $configuration->setWhiteList($value);
                break;
            case 'blackList':
                $configuration->setBlackList($value);
                break;
            default:
                throw new \RuntimeException(sprintf("Unknown VCR configuration option \"%s\"", $option));
        }
    }

    /**
     * A test ended.
     *
     * @param Test  $test
     * @param float $time
     */
    public function endTest(Test $test, $time)
    {
        VCR::turnOff();
    }

    /**
     * A test suite started.
     *
     * @param TestSuite $suite
     */
    public function startTestSuite(TestSuite $suite)
    {
    }

    /**
     * A test suite ended.
     *
     * @param TestSuite $suite
     */
    public function endTestSuite(TestSuite $suite)
    {
    }
}
