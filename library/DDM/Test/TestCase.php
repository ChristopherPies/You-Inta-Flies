<?php

/**
 * PHPUnit test case
 * Compatible with DDM_Test_Listener_Dashboard
 *
 * This class uses these config constants:
 * - PROJECT_NAME (optional, default=null)
 * - PROMPT_BEFORE_TEST (optional, default=0)
 */
abstract class DDM_Test_TestCase
    extends PHPUnit_Framework_TestCase
    implements DDM_Test_Listener_Dashboard_TestCaseInterface
{

    /**
     * Returns whether the dashboard listener is enabled.
     * The @dashboardListenerEnabled annotation is used when this is null.
     *
     * @var boolean|null
     */
    protected $dashboardListenerEnabled = null;

    /**
     * Name of the project the test case belongs to
     * @var string|null
     */
    protected $projectName = null;

    /**
     * Name of the suite the test case belongs to
     * @var string|null
     */
    protected $suiteName = null;

    /**
     * Screenshot captured of browser
     *
     * @var string|boolean
     */
    protected $screenshot = false;

    /**
     * Unique ID to test
     *
     * @var float
     */
    protected $testId;

    /**
     * Status message from selenium when test fails
     *
     * @var string
     */
    protected $statusDetails = '';

    /**
     * Reference location. Used to save URL of failures for test listener
     *
     * @var string
     */
    protected $location = '';

    /**
     * Whether to prompt user before each test run. If this is null, the
     * config constant PROMPT_BEFORE_TEST will be used if defined.
     *
     * @var boolean|null
     */
    protected $promptBeforeTest = null;

    /**
     * Overriden to optionally prompt user to confirm each test run
     *
     * @return mixed
     */
    protected function runTest() {
        if ($this->getPromptBeforeTest()) {
            $msg = sprintf('Run %s::%s (y/n)? ', get_class($this), $this->getName());
            $confirm = strtolower($this->getUserInput($msg, false));
            if ($confirm != 'y' && $confirm != 'yes') {
                $this->markTestSkipped('Skipped by user');
            }
        }
        return parent::runTest();
    }

    /**
     * Prompts the user to enter something and returns the input
     *
     * @param string $message
     *
     * @return string
     */
    protected function getUserInput($message, $skipIfEmpty = true, $skipMessage = null) {
        fwrite(STDOUT, PHP_EOL . $message);

        $input = trim(fgets(STDIN));
        if ($skipIfEmpty && $input === '') {
            $this->markTestSkipped($skipMessage);
        }

        return $input;
    }

    /**
     * Returns whether to prompt user before the test.
     * If the class property is null, PROMPT_BEFORE_TEST will be used if defined.
     * If the class property and PROMPT_BEFORE_TEST are null, the default is false.
     *
     * @return boolean
     */
    public function getPromptBeforeTest() {
        if ($this->promptBeforeTest === null) {
            $this->promptBeforeTest = defined('PROMPT_BEFORE_TEST') && PROMPT_BEFORE_TEST;
        }
        return $this->promptBeforeTest;
    }

    /**
     * Sets whether to prompt before the test
     *
     * @param boolean $flag
     */
    public function setPromptBeforeTest($flag) {
        $this->promptBeforeTest = (bool) $flag;
    }

    /**
     * Returns whether the dashboard listener is enabled.
     * The @dashboardListenerEnabled annotation is used when the class property is null.
     * if the property and annotation are null, the default is true.
     *
     * @return boolean
     */
    public function getDashboardListenerEnabled() {
        if ($this->dashboardListenerEnabled === null) {
            $this->dashboardListenerEnabled = true; // default to true

            $annotations = $this->getAnnotations();
            $key = 'dashboardListenerEnabled';
            $value = null;

            if (isset($annotations['method'][$key])) {
                $value = $annotations['method'][$key];
            } else if (isset($annotations['class'][$key])) {
                $value = $annotations['class'][$key];
            }
            if (is_array($value)) {
                $value = strtolower(trim(reset($value)));
                $this->dashboardListenerEnabled = ($value != 'disabled' && $value != '0' && $value != 'false');
            }
        }
        return $this->dashboardListenerEnabled;
    }

    /**
     * Sets whether the dashboard listener is enabled.
     *
     * @param boolean $flag
     */
    public function setDashboardListenerEnabled($flag) {
        $this->dashboardListenerEnabled = (bool) $flag;
    }

    /**
     * Returns the testId
     *
     * @return string
     */
    public function getTestId() {
        if ($this->testId === null) {
            $this->testId = time();
        }
        return $this->testId;
    }

    /**
     * Returns the projectName
     *
     * @return string $projectName
     */
    public function getProjectName() {
        if ($this->projectName === null) {
            // Default to the project name constant
            if (defined('PROJECT_NAME')) {
                $this->setProjectName(PROJECT_NAME);
            }
            $annotations = $this->getAnnotations();

            $project = null;

            // If we have any annotations, then override name with the annotation
            if (isset($annotations['method']['project'])) {
                $project = $annotations['method']['project'];
            } else if (isset($annotations['class']['project'])) {
                $project = $annotations['class']['project'];
            }

            if ($project !== null) {
                $project = reset($project);
                $this->setProjectName($project);
            }
        }
        return $this->projectName;
    }

    /**
     * Sets the projectName
     *
     * @param string $projectName
     */
    public function setProjectName($projectName) {
        if ($projectName !== null) {
            $projectName = strtolower($projectName);
        }
        $this->projectName = $projectName;
    }

    /**
     * Returns the suiteName
     *
     * @return string
     */
    public function getSuiteName() {
        if ($this->suiteName === null) {
            $annotations = $this->getAnnotations();

            $suite = array();

            if (isset($annotations['method']['suite'])) {
                $suite = $annotations['method']['suite'];
            } else if (isset($annotations['class']['suite'])) {
                $suite = $annotations['class']['suite'];
            }

            $suite = reset($suite);
            $this->setSuiteName($suite);
        }

        return $this->suiteName;
    }

    /**
     * Sets the suiteName
     * @param string $suiteName
     */
    public function setSuiteName($suiteName) {
        if ($suiteName !== null) {
            $suiteName = trim(strtolower($suiteName));
        }
        $this->suiteName = $suiteName;
    }

    /**
     * Returns the screenshot
     *
     * @return string $screenshot
     */
    public function getScreenshot() {
        return $this->screenshot;
    }

    /**
     * Sets the screenshot
     *
     * @param string $screenshot
     * @param boolean $encode OPTIONAL
     */
    public function setScreenshot($screenshot, $encode = true) {
        if ($encode) {
            $screenshot = base64_encode($screenshot);
        }
        $this->screenshot = $screenshot;
    }

    /**
     * Returns the status details
     *
     * @return string $statusDetails
     */
    public function getStatusDetails() {
        return $this->statusDetails;
    }

    /**
     * Sets the status details
     *
     * @param string $details
     */
    public function setStatusDetails($details) {
        $this->statusDetails = $details;
    }

    /**
     * Returns the location
     *
     * @return string $location
     */
    public function getLocation() {
        return $this->location;
    }

    /**
     * Sets the location
     *
     * @param string $location
     */
    public function setLocation($location) {
        $this->location = $location;
    }

}
