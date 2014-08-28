<?php
/**
 * Created by PhpStorm.
 * User: cpies
 * Date: 6/30/14
 * Time: 10:43 AM
 */

class DDM_Form_Element_BotCatcher extends Zend_Form_Element_Hidden {
    public $helper = "botCatcher";

    public function __construct($specs, $options = null) {
        parent::__construct($specs, $options);

        $validator = new Zend_Validate_Identical("SUBM!TT3D 8Y A HUMAN");
        $this->addValidator($validator);
    }
}