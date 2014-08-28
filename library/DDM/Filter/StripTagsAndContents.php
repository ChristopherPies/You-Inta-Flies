<?php

class DDM_Filter_StripTagsAndContents extends Zend_Filter_StripTags
{

    public function filter($value)
    {
        $allowed = $this->getTagsAllowed();
        if(!count($allowed)) {
            $allowed = null;
        } else {
            $allowed = array_keys($allowed);
        }        
        return strip_tag_and_contents($value, $allowed);

    }

}
