<?php

class DDM_Test_Listener_Dashboard implements PHPUnit_Framework_TestListener {
    /**
     * URL of dashboard being posted to
     * @var string|null
     */
    protected $dashboardUrl = null;

    public function __construct($dashboardUrl) {
        $this->setDashboardUrl($dashboardUrl);
    }

    /**
     * Returns the url for the dashboard being posted to
     * @return string
     */
    public function getDashboardUrl() {
        return $this->dashboardUrl;
    }

    /**
     * Sets the url for the dashboard being posted to
     * @param string $dashboardUrl
     */
    public function setDashboardUrl($dashboardUrl) {
        $this->dashboardUrl = $dashboardUrl;
    }
    
    /**
     * Returns whether this listener is enabled and can run with the $test.
     * 
     * @param PHPUnit_Framework_Test $test
     * 
     * @return boolean
     */
    protected function getListenerEnabledForTest(PHPUnit_Framework_Test $test) {
        return ($test instanceof DDM_Test_Listener_Dashboard_TestCaseInterface)
                && $test->getDashboardListenerEnabled()
                && $this->getDashboardUrl();
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
    }

    public function startTest(PHPUnit_Framework_Test $test) {
    }

    /**
     * Posts test results to dashboard after test finishes running
     * @param  PHPUnit_Framework_Test $test
     * @param  float $time
     * @return
     */
    public function endTest(PHPUnit_Framework_Test $test, $time) {
        if ($this->getListenerEnabledForTest($test)) {
            $params = array();
            $params['project_title'] = $test->getProjectName();
            $params['suite_title'] = $test->getSuiteName();

            // If we don't have a project or suite title, don't report to the dashboard
            if ($params['project_title'] === null || $params['suite_title'] === null) {
                return;
            }

            $params['test_case_title'] = $test->getName();
            $params['phpunit_test_id'] = $test->getTestId();
            $params['phpunit_status_id'] = $test->getStatus();
            $params['status_message'] = $test->getLocation() . ' - ' . $test->getStatusMessage();
            $params['status_details'] = $test->getStatusDetails();
            $params['screenshot'] = $test->getScreenshot();

            $ch = curl_init($this->getDashboardUrl() . '/api/test-case/saveresult');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
        }
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
    }
    
}
