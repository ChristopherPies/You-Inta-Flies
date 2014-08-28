<?php
#http://davidstutz.github.io/bootstrap-multiselect/
class DDM_Form_Element_MultiSelect extends Zend_Form_Element_Select {

    public function __construct($spec, $options = null)
    {
        if(empty($options['class'])) {
            $options['class'] = 'multiselect';
        }
        else if(!strstr($options['class'], 'multiselect')) {
            $options['class'] .= ' multiselect';
        }
        parent::__construct($spec, $options);
        $this->setIsArray(true);
        $this->getView()->headScript()->appendFile('/js/lib/bootstrapPlugins/bootstrap-multiselect.js');

        $buttonClass = empty($options['buttonClass']) ? 'btn' : $options['buttonClass'];
        $buttonWidth = empty($options['buttonWidth']) ? 'auto' : $options['buttonWidth'];
        $buttonTextFunction = empty($options['buttonTextFunction']) ? 'function(options) {
                            if (options.length == 0) {
                                return \'None selected <b class="caret"></b>\';
                            }
                            else if (options.length > 3) {
                                return options.length + \' selected <b class="caret"></b>\';
                            }
                            else {
                                var selected = \'\';
                                options.each(function() {
                                    selected += $(this).text() + \', \';
                                });
                                return selected.substr(0, selected.length -2) + \' <b class="caret"></b>\';
                            }
                        }' : $options['buttonTextFunction'];
        $buttonContainer = empty($options['buttonContainer']) ? '<div class="btn-group" />' : $options['buttonContainer'];
        $maxHeight = empty($options['maxHeight']) ? 'false' : "'".$options['maxHeight']."'";
        $enableFiltering = empty($options['enableFiltering']) ? 'false' : $options['enableFiltering'];
        $enableCaseInsensitiveFiltering = empty($options['enableCaseInsensitiveFiltering']) ? 'false' : $options['enableCaseInsensitiveFiltering'];
        $filterPlaceholder = empty($options['filterPlaceholder']) ? 'Search' : $options['filterPlaceholder'];
        $onChange = empty($options['onChange']) ? 'null' : $options['onChange'];
        $countItemDisplay = empty($options['countItemDisplay']) ? 'null' : $options['countItemDisplay'];
        $dropRight = empty($options['dropRight']) ? 'false' : $options['dropRight'];
        $selectedClass = empty($options['selectedClass']) ? 'active' : $options['selectedClass'];
        $includeSelectAllOption = empty($options['includeSelectAllOption']) ? 'false' : $options['includeSelectAllOption'];
        $selectAllValue = empty($options['selectAllValue']) ? 'multiselect-all' : $options['selectAllValue'];
        $selectAllText = empty($options['selectAllText']) ? 'Select all' : $options['selectAllText'];
        $preventInputChangeEvent = empty($options['preventInputChangeEvent']) ? 'false' : $options['preventInputChangeEvent'];
        $filterBehavior = empty($options['filterBehavior']) ? 'text' : $options['filterBehavior'];
        $nonSelectedText = empty($options['nonSelectedText']) ? 'None selected' : $options['nonSelectedText'];
        $nSelectedText = empty($options['nSelectedText']) ? 'selected' : $options['nSelectedText'];

        $script = 'var multiselect_'.$spec.';
            $(document).ready(function() {
                    multiselect_'.$spec.' = $(\'select[id^="'.$spec.'"\').multiselect({
                        buttonClass: \''.$buttonClass.'\',
                        buttonWidth: \''.$buttonWidth.'\',
                        buttonContainer: \''.$buttonContainer.'\',
                        maxHeight: '.$maxHeight.',
                        enableFiltering: '.$enableFiltering.',
                        onChange: '.$onChange.',
                        buttonText: '.$buttonTextFunction.',
                        checkboxClass: \''.$options['checkboxClass'].'\',
                        selectId: \''.$options['selectId'].'\',
                        itemName: \''.$options['itemName'].'\',
                        countItemDisplay: '.$countItemDisplay.',
                        enableCaseInsensitiveFiltering: '.$enableCaseInsensitiveFiltering.',
                        filterPlaceholder: \''.$filterPlaceholder.'\',
                        dropRight: '.$dropRight.',
                        selectedClass: \''.$selectedClass.'\',
                        includeSelectAllOption: '.$includeSelectAllOption.',
                        selectAllValue: \''.$selectAllValue.'\',
                        selectAllText: \''.$selectAllText.'\',
                        filterBehavior: \''.$filterBehavior.'\',
                        preventInputChangeEvent: '.$preventInputChangeEvent.',
                        nonSelectedText: \''.$nonSelectedText.'\',
                        nSelectedText: \''.$nSelectedText.'\'
                    });
                    $(\'select[id^="'.$spec.'"\').multiselect("updateButtonText");
                });';
        $this->getView()->headScript()->appendScript($script);
    }
}