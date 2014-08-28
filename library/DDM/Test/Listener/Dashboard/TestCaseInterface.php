<?php

/**
 * Interface that a test case must implement to use DDM_Test_Listener_Dashboard
 */
interface DDM_Test_Listener_Dashboard_TestCaseInterface {
    
    /**
     * Returns whether the dashboard listener is enabled
     * 
     * @return boolean 
     */
    public function getDashboardListenerEnabled();
    
    /**
     * Returns the testId
     *
     * @return string
     */
    public function getTestId();
    
    /**
     * Returns the projectName
     *
     * @return string $projectName
     */
    public function getProjectName();

    /**
     * Sets the projectName
     *
     * @param string $projectName
     */
    public function setProjectName($projectName);

    /**
     * Returns the suiteName
     *
     * @return string
     */
    public function getSuiteName();

    /**
     * Sets the suiteName
     * @param string $suiteName
     */
    public function setSuiteName($suiteName);

    /**
     * Returns the screenshot
     *
     * @return string|false $screenshot
     */
    public function getScreenshot();

    /**
     * Sets the screenshot
     *
     * @param string $screenshot
     * @param boolean $encode OPTIONAL
     */
    public function setScreenshot($screenshot, $encode = true);

    /**
     * Returns the status details
     *
     * @return string $statusDetails
     */
    public function getStatusDetails();

    /**
     * Sets the status details
     *
     * @param string $details
     */
    public function setStatusDetails($details);

    /**
     * Returns the location
     *
     * @return string $location
     */
    public function getLocation();

    /**
     * Sets the location
     *
     * @param string $location
     */
    public function setLocation($location);
    
}
